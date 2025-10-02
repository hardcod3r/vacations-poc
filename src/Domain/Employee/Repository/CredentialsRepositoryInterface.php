<?php

declare(strict_types=1);

namespace Domain\Employee\Repository;

use Ramsey\Uuid\UuidInterface;

interface CredentialsRepositoryInterface
{
    public function findByEmployeeId(UuidInterface $id): ?object;

    public function updateHash(UuidInterface $id, string $hash): void;

    public function upsertHash(UuidInterface $id, string $hash): void;
}
