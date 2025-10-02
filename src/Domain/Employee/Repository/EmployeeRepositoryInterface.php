<?php

declare(strict_types=1);

namespace Domain\Employee\Repository;

use Domain\Employee\Entity\Employee;

/**
 * Abstraction over Employee persistence.
 */
interface EmployeeRepositoryInterface
{
    public function save(Employee $employee): void;

    public function findById(string $id): ?Employee;

    public function findByEmail(string $email): ?Employee;

    /** @return Employee[] */
    public function all(): array;

    public function delete(string $id): void;
}
