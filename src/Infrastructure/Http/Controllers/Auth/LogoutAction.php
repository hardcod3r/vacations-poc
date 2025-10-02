<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Auth;

use Application\UseCase\Auth\Logout;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Auth\RefreshRequest;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/auth/logout',
    summary: 'Logout',
    description: 'Invalidate the refresh token to log out the user.',
    tags: ['auth'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'logged_out'),
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
                    new OA\Property(property: 'message', type: 'string', example: 'Authentication required.'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Employee->value, Role::Manager->value])]
final class LogoutAction
{
    public function __construct(
        private Logout $useCase,
        private Responder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $req->getBody()->rewind();
            $raw = json_decode((string) $req->getBody(), true);

            if (! is_array($raw) || empty($raw['refresh_id']) || ! is_string($raw['refresh_id'])) {
                return $this->responder->error('VALIDATION_ERROR', 'The refresh ID is required.', 422);
            }

            $dto = RefreshRequest::fromArray($raw);     // εδώ ελέγχεται και το UUID format
            $this->useCase->execute($dto->refreshId);

            return $this->responder->success(['status' => 'logged_out'], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->responder->error('SERVER_ERROR', 'Unexpected error', 500);
        }
    }
}
