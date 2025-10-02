<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Employee;

use Domain\Employee\Enum\Role;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Response\EmployeeResource;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// employee resource
/*return [
            'id' => $this->employee->id(),
            'name' => $this->employee->name(),
            'email' => $this->employee->email(),
            'employee_code' => $this->employee->employeeCode(),
            'role' => $role,
            'role_label' => Role::from($role)->label(),
        ];*/

#[OA\Get(
    path: '/api/v1/employees',
    summary: 'List Employees',
    tags: ['employees'],
    description: 'Retrieve a list of all employees.',
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/EmployeeResource'),
                    ),
                    new OA\Property(
                        property: 'meta',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'count', type: 'integer', example: 2),
                        ],
                    ),
                ],
            ),
        ),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]

#[Authorize([Role::Manager->value])]
final class IndexEmployeeAction
{
    public function __construct(
        private EmployeeRepositoryInterface $repo,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $employees = $this->repo->all();
            $resources = \array_map(fn ($e) => new EmployeeResource($e), $employees);

            return $this->responder->success($resources, 200, [
                'count' => \count($resources),
            ]);
        } catch (\PDOException $e) {
            return $this->responder->error('DATABASE_ERROR', 'An error occurred while fetching employees', 500);
        }
    }
}
