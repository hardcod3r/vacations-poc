<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Employee;

use Application\UseCase\UserManagement;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Employee\CreateEmployeeRequest;
use Interface\Http\Response\EmployeeResource;
use Monolog\Logger;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/employees',
    summary: 'Create Employee',
    tags: ['employees'],
    description: 'Create a new employee with the provided details.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(
                    property: 'employeeCode',
                    type: 'string',
                    example: '1234567',
                    description: 'Unique employee code',
                    minLength: 7,
                    maxLength: 7,
                ),
                new OA\Property(
                    property: 'role',
                    type: 'string',
                    example: 100,
                    description: 'Role of the employee. 1 - employee, 100 - manager',
                    enum: [1, 100],
                ),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Created',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'created'),
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
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid input data'),
                ],
            ),
        ),
        new OA\Response(
            response: 409,
            description: 'Conflict',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'CONFLICT'),
                    new OA\Property(property: 'message', type: 'string', example: 'Email or employee_code already exists'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Manager->value])]
final class CreateEmployeeAction
{
    public function __construct(
        private UserManagement $useCase,
        private Responder $responder,
        private Logger $logger,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $data = \json_decode((string) $req->getBody(), true);

            if (!\is_array($data)) {
                throw new \InvalidArgumentException('Invalid request body');
            }

            /** @var array{name?: string, email?: string, employee_code?: string, role?: int|string} $typed */
            $typed = $data;

            $dto = CreateEmployeeRequest::fromArray($typed);

            $employee = $this->useCase->create(
                $dto->name,
                $dto->email,
                $dto->employeeCode,
                $dto->role,
            );

            return $this->responder->success(new EmployeeResource($employee), 201);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Validation failed', ['msg' => $e->getMessage()]);

            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\PDOException $e) {
            if (($e->errorInfo[0] ?? null) === '23505') {
                return $this->responder->error('CONFLICT', 'Email or employee_code already exists', 409);
            }
            throw $e;
        }
    }
}
