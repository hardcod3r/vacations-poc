<?php

declare(strict_types=1);

namespace Infrastructure\Http\Middleware;

use Infrastructure\Http\ExceptionMapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ErrorHandlerMiddleware
{
    public function __construct(private ExceptionMapper $mapper)
    {
    }

    /**
     * @param  callable(ServerRequestInterface):ResponseInterface  $next
     */
    public function __invoke(ServerRequestInterface $req, callable $next): ResponseInterface
    {
        try {
            /** @var ResponseInterface $response */
            $response = $next($req);

            return $response;
        } catch (\Throwable $e) {
            return $this->mapper->toResponse($e);
        }
    }
}
