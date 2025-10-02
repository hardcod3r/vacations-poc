<?php

declare(strict_types=1);

use Application\UseCase\UserManagement;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Mockery as m;

it('deletes all vacations then the employee', function () {
    $empId = '33333333-3333-3333-3333-333333333333';

    $empRepo = m::mock(EmployeeRepositoryInterface::class);
    $vacRepo = m::mock(VacationRequestRepositoryInterface::class);

    $empRepo->shouldReceive('findById')->with($empId)
        ->andReturn(new Employee($empId, 'Jane', 'jane@example.com', 'EC123', 100));

    $v1 = new VacationRequest('R1', $empId, new DateTimeImmutable(), new DateTimeImmutable(), new DateTimeImmutable(), 'r');
    $v2 = new VacationRequest('R2', $empId, new DateTimeImmutable(), new DateTimeImmutable(), new DateTimeImmutable(), 'r');

    $vacRepo->shouldReceive('findByEmployee')->with($empId)->andReturn([$v1, $v2]);
    $vacRepo->shouldReceive('delete')->with('R1')->once();
    $vacRepo->shouldReceive('delete')->with('R2')->once();

    $empRepo->shouldReceive('delete')->with($empId)->once();

    $uc = new UserManagement($empRepo, $vacRepo); // η νέα υπογραφή που βάλαμε
    $uc->delete($empId);

    expect(true)->toBeTrue();
});
