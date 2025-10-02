<?php

declare(strict_types=1);

use Application\UseCase\DeleteOwnVacationRequest;
use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Mockery as m;

function vr(string $id, string $eid, VacationStatus $st = VacationStatus::Pending): VacationRequest
{
    $now = new DateTimeImmutable('2025-01-01');

    return new VacationRequest($id, $eid, $now, $now, $now, 'reason', $st);
}

it('deletes pending request of owner', function () {
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('R1')->andReturn(vr('R1', 'E1', VacationStatus::Pending));
    $repo->shouldReceive('delete')->with('R1')->once();

    $uc = new DeleteOwnVacationRequest($repo);
    $uc->execute('R1', 'E1');

    expect(true)->toBeTrue();
});

it('forbids deleting request of another employee', function () {
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('R1')->andReturn(vr('R1', 'E2', VacationStatus::Pending));

    $uc = new DeleteOwnVacationRequest($repo);
    $uc->execute('R1', 'E1');
})->throws(DomainException::class, 'forbidden');

it('rejects deleting non-pending request', function () {
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('R1')->andReturn(vr('R1', 'E1', VacationStatus::Approved));

    $uc = new DeleteOwnVacationRequest($repo);
    $uc->execute('R1', 'E1');
})->throws(DomainException::class, 'not_pending');
