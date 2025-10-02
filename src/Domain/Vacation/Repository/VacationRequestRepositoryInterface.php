<?php

declare(strict_types=1);

namespace Domain\Vacation\Repository;

use Domain\Vacation\Entity\VacationRequest;

/**
 * Abstraction over VacationRequest persistence.
 */
interface VacationRequestRepositoryInterface
{
    public function save(VacationRequest $request): void;

    public function findById(string $id): ?VacationRequest;

    /** @return VacationRequest[] */
    public function findPending(): array;

    /** @return VacationRequest[] */
    public function findByEmployee(string $employeeId): array;

    public function delete(string $id): void;
}
