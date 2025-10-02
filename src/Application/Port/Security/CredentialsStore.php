<?php

declare(strict_types=1);

namespace Application\Port\Security;

interface CredentialsStore
{
    public function getHash(string $employeeId): ?string;

    public function setHash(string $employeeId, string $hash, \DateTimeImmutable $updatedAt): void;
}
