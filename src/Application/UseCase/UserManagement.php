<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Ramsey\Uuid\Uuid;

final class UserManagement
{
    public function __construct(
        private EmployeeRepositoryInterface $repo,
        private VacationRequestRepositoryInterface $vacations,
    ) {
    }

    public function create(string $name, string $email, string $employeeCode, int $role): Employee
    {
        $employee = new Employee(Uuid::uuid4()->toString(), $name, $email, $employeeCode, $role);
        $this->repo->save($employee);

        return $employee;
    }

    public function update(string $id, string $name, string $email, string $employeeCode, int $role): void
    {
        $employee = $this->repo->findById($id);

        if ($employee === null) {
            throw new \RuntimeException('Employee not found');
        }
        $this->repo->save(new Employee($id, $name, $email, $employeeCode, $role));
    }

    public function delete(string $id): void
    {
        $employee = $this->repo->findById($id);

        if ($employee === null) {
            throw new \RuntimeException('Employee not found');
        }

        // 1) delete all vacation requests of this employee
        foreach ($this->vacations->findByEmployee($id) as $req) {
            $this->vacations->delete($req->id());
        }

        // 2) delete the employee
        //    Credentials + refresh tokens will be removed due to CASCADE.
        $this->repo->delete($id);
    }

    public function find(string $id): Employee
    {
        $employee = $this->repo->findById($id);

        if ($employee === null) {
            throw new \RuntimeException('Employee not found');
        }

        return $employee;
    }
}
