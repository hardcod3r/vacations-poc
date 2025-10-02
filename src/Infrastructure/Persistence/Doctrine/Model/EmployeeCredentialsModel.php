<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine\Model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'employee_credentials')]
final class EmployeeCredentialsModel
{
    #[ORM\Id]
    #[ORM\Column(name: 'employee_id', type: 'string', length: 36)]
    public string $employeeId;

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
    public string $passwordHash;

    #[ORM\Column(name: 'password_algo', type: 'string', length: 32)]
    public string $passwordAlgo = 'argon2id';

    #[ORM\Column(type: 'smallint')]
    public int $status = 1; // 1=active, 0=locked

    #[ORM\Column(name: 'updated_at', type: 'datetimetz_immutable')]
    public \DateTimeImmutable $updatedAt;
}
