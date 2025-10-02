<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Infrastructure\Persistence\Doctrine\Model\EmployeeCredentialsModel;

final class CredentialsRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function find(string $employeeId): ?EmployeeCredentialsModel
    {
        return $this->em->find(EmployeeCredentialsModel::class, $employeeId);
    }

    public function upsert(EmployeeCredentialsModel $m): void
    {
        $this->em->persist($m);
        $this->em->flush();
    }

    public function lock(string $employeeId): void
    {
        if ($m = $this->find($employeeId)) {
            $m->status = 0;
            $this->em->flush();
        }
    }

    public function unlock(string $employeeId): void
    {
        if ($m = $this->find($employeeId)) {
            $m->status = 1;
            $this->em->flush();
        }
    }
}
