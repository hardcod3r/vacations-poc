<?php

declare(strict_types=1);

namespace Infrastructure\Http\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RoleMiddleware
{
    /**
     * @param  int[]  $allowedRoles
     */
    public function __construct(
        private Psr17Factory $rf,
        private array $allowedRoles,
    ) {
    }

    /**
     * @param  callable(ServerRequestInterface):ResponseInterface  $next
     */
    public function __invoke(ServerRequestInterface $req, callable $next): ResponseInterface
    {
        $attr = $req->getAttribute('role');
        $role = \is_int($attr) ? $attr : (\is_string($attr) ? (int) $attr : 0);

        if (!\in_array($role, $this->allowedRoles, true)) {
            $res = $this->rf->createResponse(403)
                ->withHeader('Content-Type', 'application/json');

            $json = \json_encode(
                [
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => "Access denied for role {$role}",
                        'allowed' => $this->allowedRoles,
                    ],
                ],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );

            $res->getBody()->write($json);

            return $res;
        }

        return $next($req);
    }
}
