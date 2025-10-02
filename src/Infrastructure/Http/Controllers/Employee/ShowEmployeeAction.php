<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Employee;

use Domain\Employee\Enum\Role;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Employee\ShowEmployeeRequest;
use Interface\Http\Response\EmployeeResource;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Get(
    path: '/api/v1/employees/{id}',
    summary: 'Show Employee',
    tags: ['employees'],
    description: 'Retrieve detailed information about a specific employee by their ID.',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Employee ID',
            schema: new OA\Schema(type: 'string', format: 'uuid'),
        ),
    ],
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
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(
            response: 422,
            description: 'Validation Error',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'VALIDATION_ERROR'),
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid UUID string: ...'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Manager->value])]
final class ShowEmployeeAction
{
    public function __construct(
        private EmployeeRepositoryInterface $repo,
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

            $dto = ShowEmployeeRequest::fromId($idAttr);

            $employee = $this->repo->findById($dto->id);

            if ($employee === null) {
                return $this->responder->error('NOT_FOUND', 'Employee not found', 404);
            }

            return $this->responder->success(new EmployeeResource($employee), 200);

        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
