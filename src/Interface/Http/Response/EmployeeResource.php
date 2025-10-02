<?php

declare(strict_types=1);

namespace Interface\Http\Response;

use Domain\Employee\Entity\Employee;
use Domain\Employee\Enum\Role;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EmployeeResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'employee_code', type: 'string', pattern: '^\d{7}$'),
        new OA\Property(property: 'role', type: 'integer', enum: [1, 100]),
        new OA\Property(property: 'role_label', type: 'string', enum: ['employee', 'manager']),
    ],
)]
final class EmployeeResource implements JsonSerializable
{
    public function __construct(private Employee $employee)
    {
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     employee_code: string,
     *     role: int,
     *     role_label: string
     * }
     */
    public function jsonSerialize(): array
    {
        $role = (int) $this->employee->role();

        return [
            'id' => $this->employee->id(),
            'name' => $this->employee->name(),
            'email' => $this->employee->email(),
            'employee_code' => $this->employee->employeeCode(),
            'role' => $role,
            'role_label' => Role::from($role)->label(),
        ];
    }
}
