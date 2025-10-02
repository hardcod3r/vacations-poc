<?php

declare(strict_types=1);

namespace Domain\Employee\Services;

use Domain\Employee\Entity\Employee;
use Domain\Employee\Enum\Role;
use Domain\Employee\Repository\EmployeeRepositoryInterface;

final class EmployeeService
{
    public function __construct(
        private EmployeeRepositoryInterface $repo,
    ) {}

    public function ensureUniqueEmail(Employee $employee): void
    {
        $existing = $this->repo->findByEmail($employee->email());

        if ($existing !== null && $existing->id() !== $employee->id()) {
            throw new \DomainException('Email already in use');
        }
    }

    public function ensureValidRole(Employee $employee): void
    {
        if (! \in_array($employee->role(), Role::all(), true)) {
            throw new \DomainException('Invalid role value');
        }
    }
}
