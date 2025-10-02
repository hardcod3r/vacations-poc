<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine;

use Domain\Employee\Entity\Employee;
use Infrastructure\Persistence\Doctrine\Model\EmployeeModel;

final class EmployeeMapper
{
    public static function toDomain(EmployeeModel $m): Employee
    {
        return new Employee($m->id, $m->name, $m->email, $m->employeeCode, $m->role);
    }

    public static function toModel(Employee $e, ?EmployeeModel $m = null): EmployeeModel
    {
        $m ??= new EmployeeModel();
        $m->id = $e->id();
        $m->name = $e->name();
        $m->email = $e->email();
        $m->employeeCode = $e->employeeCode();
        $m->role = $e->role();

        return $m;
    }
}
