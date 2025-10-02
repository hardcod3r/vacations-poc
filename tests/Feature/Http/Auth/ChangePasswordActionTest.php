<?php

declare(strict_types=1);

use Application\Port\Security\CredentialsStore;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Application\UseCase\Auth\ChangeOwnPassword;
use Infrastructure\Http\Controllers\Auth\ChangePasswordAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/auth/password → 200', function () {
    $store = m::mock(CredentialsStore::class);
    $hasher = m::mock(PasswordHasher::class);
    $clock = m::mock(Clock::class);

    $uc = new ChangeOwnPassword($store, $hasher, $clock);

    $store->shouldReceive('getHash')->with('E1')->andReturn('old-hash');
    $hasher->shouldReceive('verify')->with('Old@1234', 'old-hash')->andReturnTrue();
    $hasher->shouldReceive('verify')->with('New@1234', 'old-hash')->andReturnFalse();
    $hasher->shouldReceive('hash')->with('New@1234')->andReturn('new-hash');
    $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2025-10-01 12:00:00'));
    $store->shouldReceive('setHash')->with('E1', 'new-hash', m::type(DateTimeImmutable::class))->once();

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new ChangePasswordAction($uc, $responder);

    $req = $rf->createServerRequest('POST', '/api/v1/auth/password')
        ->withAttribute('employee_id', 'E1');
    $req->getBody()->write(json_encode(['old_password' => 'Old@1234', 'new_password' => 'New@1234']));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(200);
    $data = json_decode((string) $resp->getBody(), true);
    expect($data)->toMatchArray(['data' => ['status' => 'ok'], 'meta' => []]);
});

it('POST /api/v1/auth/password → 422 on invalid old', function () {
    $store = m::mock(CredentialsStore::class);
    $hasher = m::mock(PasswordHasher::class);
    $clock = m::mock(Clock::class);

    $uc = new ChangeOwnPassword($store, $hasher, $clock);

    $store->shouldReceive('getHash')->with('E1')->andReturn('old-hash');
    $hasher->shouldReceive('verify')->with('Bad@1234', 'old-hash')->andReturnFalse();

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new ChangePasswordAction($uc, $responder);

    $req = $rf->createServerRequest('POST', '/api/v1/auth/password')
        ->withAttribute('employee_id', 'E1');
    $req->getBody()->write(json_encode(['old_password' => 'Bad@1234', 'new_password' => 'New@1234']));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(422);
});
