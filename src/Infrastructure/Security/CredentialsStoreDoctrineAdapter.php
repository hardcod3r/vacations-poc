<?php

declare(strict_types=1);

namespace Infrastructure\Security;

use Application\Port\Security\CredentialsStore;
use Infrastructure\Persistence\Doctrine\Auth\CredentialsRepository;
use Infrastructure\Persistence\Doctrine\Model\EmployeeCredentialsModel;

final class CredentialsStoreDoctrineAdapter implements CredentialsStore
{
    public function __construct(private CredentialsRepository $repo)
    {
    }

    public function getHash(string $employeeId): ?string
    {
        $m = $this->repo->find($employeeId);

        return $m ? $m->passwordHash : null;
    }

    public function setHash(string $employeeId, string $hash, \DateTimeImmutable $updatedAt): void
    {
        $m = $this->repo->find($employeeId) ?? new EmployeeCredentialsModel();
        $m->employeeId = $employeeId;
        $m->passwordHash = $hash;
        $m->passwordAlgo = 'argon2id';
        $m->status = 1;
        $m->updatedAt = $updatedAt;
        $this->repo->upsert($m);
    }
}
