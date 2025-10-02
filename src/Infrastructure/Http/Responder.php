<?php

declare(strict_types=1);

namespace Infrastructure\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

final class Responder
{
    public function __construct(private Psr17Factory $rf)
    {
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function success(mixed $data, int $status = 200, array $meta = []): ResponseInterface
    {
        $res = $this->rf->createResponse($status)
            ->withHeader('Content-Type', 'application/json');

        $json = \json_encode(
            ['data' => $data, 'meta' => $meta],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        $res->getBody()->write($json);

        return $res;
    }

    public function error(string $code, string $message, int $status = 400): ResponseInterface
    {
        $res = $this->rf->createResponse($status)
            ->withHeader('Content-Type', 'application/json');

        $json = \json_encode(
            ['error' => ['code' => $code, 'message' => $message]],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        $res->getBody()->write($json);

        return $res;
    }
}
