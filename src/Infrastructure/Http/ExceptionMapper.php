<?php

declare(strict_types=1);

namespace Infrastructure\Http;

use Application\Exception\ForbiddenException;
use Application\Exception\NotFoundException;
use Application\Exception\UnauthorizedException;
use Application\Exception\ValidationException;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

final class ExceptionMapper
{
    public function __construct(
        private Psr17Factory $rf,
        private Logger $logger,
    ) {}

    public function toResponse(\Throwable $e): ResponseInterface
    {
        $status = 500;
        $payload = [
            'error' => [
                'code' => 'INTERNAL',
                'message' => 'Unexpected server error',
            ],
        ];

        switch (true) {
            case $e instanceof ValidationException:
                $status = 422;
                $payload['error'] = [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->details(),
                ];
                break;

            case $e instanceof NotFoundException:
                $status = 404;
                $payload['error'] = [
                    'code' => 'NOT_FOUND',
                    'message' => $e->getMessage(),
                ];
                break;

            case $e instanceof UnauthorizedException:
                $status = 401;
                $payload['error'] = [
                    'code' => 'UNAUTHORIZED',
                    'message' => $e->getMessage(),
                ];
                break;

            case $e instanceof ForbiddenException:
                $status = 403;
                $payload['error'] = [
                    'code' => 'FORBIDDEN',
                    'message' => $e->getMessage(),
                ];
                break;

            case $e instanceof \DomainException:
                $status = 400;
                $payload['error'] = [
                    'code' => 'BAD_REQUEST',
                    'message' => $e->getMessage(),
                ];
                break;
            case $e instanceof \RuntimeException:
                $status = 429;
                $payload['error'] = [
                    'code' => 'TOO_MANY_REQUESTS',
                    'message' => $e->getMessage(),
                ];
                break;
        }

        // Logging with appropriate level
        $level = $status >= 500 ? 'error' : 'warning';
        $this->logger->{$level}($e->getMessage(), [
            'exception' => $e,
        ]);

        $res = $this->rf->createResponse($status)
            ->withHeader('Content-Type', 'application/json');

        $json = \json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $res->getBody()->write($json);

        return $res;
    }
}
