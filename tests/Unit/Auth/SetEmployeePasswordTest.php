<?php

declare(strict_types=1);

use Application\Port\Security\CredentialsStore;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Application\UseCase\Auth\SetEmployeePassword;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Mockery as m;

it('sets password for existing employee', function () {
    $employeeId = '22222222-2222-2222-2222-222222222222';

    $emps = Mockery::mock(EmployeeRepositoryInterface::class);
    $store = Mockery::mock(CredentialsStore::class);
    $hasher = Mockery::mock(PasswordHasher::class);
    $clock = Mockery::mock(Clock::class);

    $emps->shouldReceive('findById')->with($employeeId)->andReturn(
        new Employee($employeeId, 'John Doe', 'john@example.com', '1234567', 100),
    );

    $hasher->shouldReceive('hash')->with('Strong@123')->andReturn('hash');
    $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2025-10-01 12:00:00'));
    $store->shouldReceive('setHash')->with($employeeId, 'hash', Mockery::type(DateTimeImmutable::class))->once();

    $uc = new SetEmployeePassword($emps, $store, $hasher, $clock);
    $uc->execute($employeeId, 'Strong@123');

    expect(true)->toBeTrue();
});

it('fails on weak password', function () {
    $uc = new SetEmployeePassword(
        m::mock(EmployeeRepositoryInterface::class),
        m::mock(CredentialsStore::class),
        m::mock(PasswordHasher::class),
        m::mock(Clock::class),
    );
    $uc->execute('22222222-2222-2222-2222-222222222222', 'weak');
})->throws(InvalidArgumentException::class, 'weak password');

it('fails when employee not found', function () {
    $emps = m::mock(EmployeeRepositoryInterface::class);
    $emps->shouldReceive('findById')->andReturn(null);

    $uc = new SetEmployeePassword(
        $emps,
        m::mock(CredentialsStore::class),
        m::mock(PasswordHasher::class),
        m::mock(Clock::class),
    );

    $uc->execute('22222222-2222-2222-2222-222222222222', 'Strong@123');
})->throws(RuntimeException::class, 'employee_not_found');
