<?php

declare(strict_types=1);

use Application\Port\Security\CredentialsStore;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Application\UseCase\Auth\SetEmployeePassword;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Http\Controllers\Employee\SetEmployeePasswordAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/employees/{id}/password → 200', function () {
    $id = '3fa85f64-5717-4562-b3fc-2c963f66afa6';

    $emps = m::mock(EmployeeRepositoryInterface::class);
    $store = m::mock(CredentialsStore::class);
    $hasher = m::mock(PasswordHasher::class);
    $clock = m::mock(Clock::class);

    $emps->shouldReceive('findById')->with($id)
        ->andReturn(new Employee($id, 'Jane', 'jane@example.com', 'EC123', 100));
    $hasher->shouldReceive('hash')->with('Strong@123')->andReturn('hash');
    $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2025-10-01 12:00:00'));
    $store->shouldReceive('setHash')->with($id, 'hash', m::type(DateTimeImmutable::class))->once();

    $uc = new SetEmployeePassword($emps, $store, $hasher, $clock);

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new SetEmployeePasswordAction($uc, $responder);

    $req = $rf->createServerRequest('POST', "/api/v1/employees/{$id}/password")
        ->withAttribute('id', $id);
    $req->getBody()->write(json_encode(['password' => 'Strong@123']));
    $req->getBody()->rewind();

    $resp = $action($req);

    expect($resp->getStatusCode())->toBe(200);
    expect(json_decode((string) $resp->getBody(), true))
        ->toMatchArray(['data' => ['status' => 'ok'], 'meta' => []]);
});

it('POST /api/v1/employees/{id}/password → 422 weak password', function () {
    $uc = new SetEmployeePassword(
        m::mock(EmployeeRepositoryInterface::class),
        m::mock(CredentialsStore::class),
        m::mock(PasswordHasher::class),
        m::mock(Clock::class),
    );

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new SetEmployeePasswordAction($uc, $responder);

    $id = '6fa85f64-5717-4562-b3fc-2c963f66afa6';
    $req = $rf->createServerRequest('POST', "/api/v1/employees/{$id}/password")
        ->withAttribute('id', $id);
    $req->getBody()->write(json_encode(['password' => 'weak']));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(422);
});

it('POST /api/v1/employees/{id}/password → 404 not found', function () {
    $emps = m::mock(EmployeeRepositoryInterface::class);
    $emps->shouldReceive('findById')->andReturn(null);

    $uc = new SetEmployeePassword(
        $emps,
        m::mock(CredentialsStore::class),
        m::mock(PasswordHasher::class),
        m::mock(Clock::class),
    );

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new SetEmployeePasswordAction($uc, $responder);

    $id = '7fa85f64-5717-4562-b3fc-2c963f66afa6';
    $req = $rf->createServerRequest('POST', "/api/v1/employees/{$id}/password")
        ->withAttribute('id', $id);
    $req->getBody()->write(json_encode(['password' => 'Strong@123']));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(404);
});
