<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Auth;

use Application\UseCase\Auth\RefreshSession;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/auth/refresh',
    summary: 'Refresh',
    tags: ['auth'],
    description: 'Refresh the user\'s session by providing a valid refresh token.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'refresh_id', type: 'string'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'refreshed'),
                ],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Validation Error',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'VALIDATION_ERROR'),
                    new OA\Property(property: 'message', type: 'string', example: 'The refresh ID is required.'),
                ],
            ),
        ),
        new OA\Response(
            response: 401,
            description: 'Unauthorized',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'UNAUTHORIZED'),
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid refresh token'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Employee->value, Role::Manager->value])]
final class RefreshAction
{
    public function __construct(
        private RefreshSession $useCase,
        private Responder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $req->getBody()->rewind();
            $json = (string) $req->getBody();
            $decoded = json_decode($json, true);

            /** @var array<string, mixed> $data */
            $data = \is_array($decoded) ? $decoded : [];

            // accept both camel & snake
            $refreshId = $data['refresh_id'] ?? $data['refreshId'] ?? null;

            if (! \is_string($refreshId) || $refreshId === '') {
                return $this->responder->error('VALIDATION_ERROR', 'The refresh ID is required.', 422);
            }

            /** @var array{refresh_id: string} $typed */
            $typed = ['refresh_id' => $refreshId];

            $dto = \Interface\Http\Request\Auth\RefreshRequest::fromArray($typed);
            $out = $this->useCase->execute($dto->refreshId);

            return $this->responder->success($out, 200);
        } catch (\Application\Exception\UnauthorizedException $e) {
            return $this->responder->error('UNAUTHORIZED', 'Invalid refresh token', 401);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
