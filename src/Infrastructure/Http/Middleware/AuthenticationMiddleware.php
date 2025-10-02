<?php

declare(strict_types=1);

namespace Infrastructure\Http\Middleware;

use Application\Port\Security\JwtIssuer;
use Infrastructure\Http\Auth\AuthContext;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(private JwtIssuer $jwt)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $h = $request->getHeaderLine('Authorization');

        if (!\str_starts_with($h, 'Bearer ')) {
            $json = \json_encode(
                [
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Missing bearer token',
                    ],
                ],
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );

            return new Response(401, ['Content-Type' => 'application/json'], $json);
        }

        $token = \substr($h, 7);

        try {
            /** @var array<string,mixed> $claims */
            $claims = $this->jwt->parseAndVerify($token);
        } catch (\Throwable) {
            $json = \json_encode(
                [
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Invalid token',
                    ],
                ],
                JSON_THROW_ON_ERROR,
            );

            return new Response(401, ['Content-Type' => 'application/json'], $json);
        }

        $sub = isset($claims['sub']) && \is_string($claims['sub']) ? $claims['sub'] : '';
        $role = isset($claims['role']) && \is_int($claims['role']) ? $claims['role'] : 0;

        if ($sub === '' || $role === 0) {
            $json = \json_encode(
                [
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Invalid claims',
                    ],
                ],
                JSON_THROW_ON_ERROR,
            );

            return new Response(401, ['Content-Type' => 'application/json'], $json);
        }

        $ctx = new AuthContext($sub, $role);
        $request = $request
            ->withAttribute('auth', $ctx)
            ->withAttribute('employee_id', $sub)
            ->withAttribute('role', $role);

        return $handler->handle($request);
    }
}
