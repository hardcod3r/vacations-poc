<?php

declare(strict_types=1);

use Application\Exception\ValidationException;
use Application\Port\Security\CredentialsStore;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Application\UseCase\Auth\ChangeOwnPassword;
use Mockery as m;

it('changes own password', function () {
    $employeeId = '11111111-1111-1111-1111-111111111111';

    $store = m::mock(CredentialsStore::class);
    $hasher = m::mock(PasswordHasher::class);
    $clock = m::mock(Clock::class);

    $store->shouldReceive('getHash')->with($employeeId)->andReturn('old-hash');
    $hasher->shouldReceive('verify')->with('Old@1234', 'old-hash')->andReturnTrue();
    $hasher->shouldReceive('verify')->with('New@1234', 'old-hash')->andReturnFalse();
    $hasher->shouldReceive('hash')->with('New@1234')->andReturn('new-hash');
    $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2025-10-01 12:00:00'));
    $store->shouldReceive('setHash')->with($employeeId, 'new-hash', m::type(DateTimeImmutable::class))->once();

    $uc = new ChangeOwnPassword($store, $hasher, $clock);
    $uc->execute($employeeId, 'Old@1234', 'New@1234');

    expect(true)->toBeTrue();
});

it('rejects wrong old password', function () {
    $employeeId = '11111111-1111-1111-1111-111111111111';

    $store = m::mock(CredentialsStore::class);
    $hasher = m::mock(PasswordHasher::class);
    $clock = m::mock(Clock::class);

    $store->shouldReceive('getHash')->with($employeeId)->andReturn('old-hash');
    $hasher->shouldReceive('verify')->with('Bad@1234', 'old-hash')->andReturnFalse();

    $uc = new ChangeOwnPassword($store, $hasher, $clock);
    $uc->execute($employeeId, 'Bad@1234', 'New@1234');
})->throws(ValidationException::class);

it('rejects weak password', function () {
    $uc = new ChangeOwnPassword(
        m::mock(CredentialsStore::class),
        m::mock(PasswordHasher::class),
        m::mock(Clock::class),
    );

    $uc->execute('11111111-1111-1111-1111-111111111111', 'Old@1234', 'weak');
})->throws(ValidationException::class);

it('fails when credentials missing', function () {
    $store = m::mock(CredentialsStore::class);
    $store->shouldReceive('getHash')->with('11111111-1111-1111-1111-111111111111')->andReturn(null);

    $uc = new ChangeOwnPassword(
        $store,
        m::mock(PasswordHasher::class),
        m::mock(Clock::class),
    );

    $uc->execute('11111111-1111-1111-1111-111111111111', 'Old@1234', 'New@1234');
})->throws(ValidationException::class);

it('rejects same-as-old password', function () {
    $eid = '11111111-1111-1111-1111-111111111111';

    $store = m::mock(CredentialsStore::class);
    $hasher = m::mock(PasswordHasher::class);
    $clock = m::mock(Clock::class);

    $store->shouldReceive('getHash')->with($eid)->andReturn('old-hash');
    // old correct
    $hasher->shouldReceive('verify')->with('Same@1234', 'old-hash')->andReturnTrue();
    // new equals old -> verify returns true ξανά
    $hasher->shouldReceive('verify')->with('Same@1234', 'old-hash')->andReturnTrue();

    $uc = new ChangeOwnPassword($store, $hasher, $clock);
    $uc->execute($eid, 'Same@1234', 'Same@1234');
})->throws(ValidationException::class);
