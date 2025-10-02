<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Employee;

use Application\UseCase\UserManagement;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Employee\UpdateEmployeeRequest;
use Interface\Http\Response\EmployeeResource;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Put(
    path: '/api/v1/employees/{id}',
    summary: 'Update Employee',
    tags: ['employees'],
    description: 'Update the details of an existing employee by their ID.',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Employee ID',
            schema: new OA\Schema(type: 'string', format: 'uuid'),
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'employee_code', type: 'string'),
                new OA\Property(property: 'role', type: 'integer'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'object', ref: '#/components/schemas/EmployeeResource'),
                ],
            ),
        ),
        new OA\Response(
            response: 404,
            description: 'Not Found',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'NOT_FOUND'),
                    new OA\Property(property: 'message', type: 'string', example: 'Employee not found'),
                ],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Validation Error',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'VALIDATION_ERROR'),
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid input data'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Manager->value])]
final class UpdateEmployeeAction
{
    public function __construct(
        private UserManagement $useCase,
        private Responder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $idAttr = $req->getAttribute('id');

            if (! \is_string($idAttr)) {
                throw new \InvalidArgumentException('Invalid employee id');
            }
            $id = $idAttr;

            $data = \json_decode((string) $req->getBody(), true);

            if (! \is_array($data)) {
                throw new \InvalidArgumentException('Invalid request body');
            }

            /** @var array{name?: string, email?: string, employee_code?: string, role?: int|string} $typed */
            $typed = $data;

            $dto = UpdateEmployeeRequest::fromArray($typed);

            $this->useCase->update(
                $id,
                $dto->name,
                $dto->email,
                $dto->employeeCode,
                $dto->role,
            );

            $employee = $this->useCase->find($id);

            return $this->responder->success(new EmployeeResource($employee), 200);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->responder->error('NOT_FOUND', $e->getMessage(), 404);
        }
    }
}
