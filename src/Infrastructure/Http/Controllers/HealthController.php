<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers;

use Infrastructure\Http\Auth\AllowAnonymous;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Get(
    path: '/api/v1/health',
    summary: 'Health check',
    description: 'Check the health status of the application.',
    tags: ['health'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        ],
                    ),
                    new OA\Property(property: 'meta', type: 'array', items: new OA\Items(type: 'string'), example: []),
                ],
            ),
        ),
    ],
)]

#[AllowAnonymous]
final class HealthController
{
    public function __construct(private \Infrastructure\Http\Responder $responder)
    {
    }

    public function health(ServerRequestInterface $req): ResponseInterface
    {
        return $this->responder->success([
            'status' => 'ok',
        ]);
    }
}
