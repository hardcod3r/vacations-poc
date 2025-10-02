<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Employee;

use Application\UseCase\UserManagement;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Employee\DeleteEmployeeRequest;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Delete(
    path: '/api/v1/employees/{id}',
    summary: 'Delete Employee',
    tags: ['employees'],
    description: 'Delete an existing employee by their ID.',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID of the employee to delete',
            schema: new OA\Schema(type: 'string'),
        ),
    ],
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
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid ID format'),
                ],
            ),
        ),
        new OA\Response(
            response: 404,
            description: 'Not Found',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'NOT_FOUND'),
                    new OA\Property(property: 'message', type: 'string', example: 'Employee not found'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Manager->value])]
final class DeleteEmployeeAction
{
    public function __construct(
        private UserManagement $useCase,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $idAttr = $req->getAttribute('id');

            if (!\is_string($idAttr)) {
                throw new \InvalidArgumentException('Invalid employee id');
            }

            $dto = DeleteEmployeeRequest::fromId($idAttr);

            $this->useCase->delete($dto->id);

            return $this->responder->success(['status' => 'ok'], 200);

        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->responder->error('NOT_FOUND', $e->getMessage(), 404);
        }
    }
}
