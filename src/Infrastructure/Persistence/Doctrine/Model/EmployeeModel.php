<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine\Model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'employees')]
final class EmployeeModel
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $id;

    #[ORM\Column(type: 'string', length: 100)]
    public string $name;

    // Στη ΒΔ είναι CITEXT. Το δηλώνουμε string. Το constraint μένει στη ΒΔ.
    #[ORM\Column(type: 'string', length: 150)]
    public string $email;

    #[ORM\Column(name: 'employee_code', type: 'string', length: 7)]
    public string $employeeCode;

    #[ORM\Column(type: 'smallint')]
    public int $role;
}
