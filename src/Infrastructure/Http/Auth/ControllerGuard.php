<?php

declare(strict_types=1);

namespace Infrastructure\Http\Auth;

use Infrastructure\Http\Middleware\AuthenticationMiddleware;
use Infrastructure\Http\Middleware\RoleMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ControllerGuard
{
    public function __construct(
        private ContainerInterface $container,
        private Psr17Factory $rf,
    ) {}

    /**
     * @param  class-string|array{0:class-string|object,1?:string}|callable(ServerRequestInterface):(ResponseInterface)  $handler
     * @param  array<string,string>  $vars
     */
    public function run(
        ServerRequestInterface $req,
        string|array|callable $handler,
        array $vars,
    ): ResponseInterface {
        // 1) Normalize handler -> [$class, $method]
        if (\is_string($handler) && \str_contains($handler, '::')) {
            [$class, $method] = \explode('::', $handler, 2);
        } elseif (\is_array($handler)) {
            if (\is_string($handler[0])) {
                /** @var class-string $class */
                $class = $handler[0];
            } elseif (\is_object($handler[0])) {
                $class = \get_class($handler[0]);
            } else {
                throw new \InvalidArgumentException('Invalid handler[0] type');
            }
            $method = $handler[1] ?? '__invoke';
        } elseif (\is_string($handler)) {
            /** @var class-string $class */
            $class = $handler;
            $method = '__invoke';
        } else {
            // plain callable: fn (ServerRequestInterface):ResponseInterface
            // 2) Inject route params as attributes
            foreach ($vars as $k => $v) {
                $req = $req->withAttribute($k, $v);
            }

            /** @var callable(ServerRequestInterface):ResponseInterface $handler */
            return $handler($req);
        }

        /** @var class-string $class */
        $rc = new \ReflectionClass($class);
        $isPublic = ! empty($rc->getAttributes(AllowAnonymous::class));

        $authAttr = $rc->getAttributes(Authorize::class)[0] ?? null;
        /** @var int[] $allowed */
        $allowed = $authAttr ? $authAttr->newInstance()->roles : [];

        // 3) Create instance and final callable
        $instance = $this->container->get($class);

        /** @var callable(ServerRequestInterface):ResponseInterface $callable */
        $callable = [$instance, $method];

        // 4) Inject route params as attributes
        foreach ($vars as $k => $v) {
            $req = $req->withAttribute($k, $v);
        }

        // 5) Public -> No Auth
        if ($isPublic && ! $allowed) {
            return $callable($req);
        }

        // 6) Protected -> Auth -> Role -> Controller
        /** @var AuthenticationMiddleware $authMw */
        $authMw = $this->container->get(AuthenticationMiddleware::class);

        /** @var RoleMiddleware|null $roleMw */
        $roleMw = $allowed ? new RoleMiddleware($this->rf, $allowed) : null;

        return $authMw->process(
            $req,
            new class($callable, $roleMw) implements RequestHandlerInterface
            {
                /**
                 * @param  callable(ServerRequestInterface):ResponseInterface  $callable
                 */
                public function __construct(
                    private $callable,
                    private ?RoleMiddleware $roleMw,
                ) {}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    if ($this->roleMw !== null) {
                        return ($this->roleMw)(
                            $request,
                            fn (ServerRequestInterface $r): ResponseInterface => ($this->callable)($r)
                        );
                    }

                    return ($this->callable)($request);
                }
            },
        );
    }
}
