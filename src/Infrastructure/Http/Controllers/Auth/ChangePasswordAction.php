<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Auth;

use Application\Exception\ValidationException;
use Application\UseCase\Auth\ChangeOwnPassword;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Auth\ChangePasswordRequest;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/auth/change-password',
    summary: 'Change own password',
    tags: ['auth'],
    description: 'Allows an authenticated user to change their own password by providing the old password and a new password.',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'old_password', type: 'string'),
                new OA\Property(property: 'new_password', type: 'string'),
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
                    new OA\Property(property: 'status', type: 'string', example: 'ok'),
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
                    new OA\Property(property: 'message', type: 'string', example: 'The new password must be at least 8 characters.'),
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
final class ChangePasswordAction
{
    public function __construct(
        private ChangeOwnPassword $useCase,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $raw = \json_decode((string) $req->getBody(), true);

            if (!\is_array($raw)) {
                throw new \InvalidArgumentException('Invalid JSON payload');
            }

            /** @var array{old_password?: string, new_password?: string} $typed */
            $typed = $raw;

            $dto = ChangePasswordRequest::fromArray($typed);

            /** @var string|null $employeeId */
            $employeeId = $req->getAttribute('employee_id');

            if (!\is_string($employeeId)) {
                throw new \InvalidArgumentException('Invalid employee_id');
            }

            $this->useCase->execute($employeeId, $dto->old_password, $dto->new_password);

            return $this->responder->success(['status' => 'ok'], 200);
        } catch (ValidationException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
