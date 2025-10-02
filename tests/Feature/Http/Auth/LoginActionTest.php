<?php

declare(strict_types=1);

// tests/Feature/Http/Auth/LoginActionTest.php

use Application\Port\Security\JwtIssuer;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Application\UseCase\Auth\LoginEmployee;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Http\Controllers\Auth\LoginAction;
use Infrastructure\Http\Responder;
use Infrastructure\Persistence\Doctrine\Auth\CredentialsRepository;
use Infrastructure\Persistence\Doctrine\Auth\RefreshTokenRepository;
use Infrastructure\Persistence\Doctrine\Model\EmployeeCredentialsModel;
use Infrastructure\Persistence\Doctrine\Model\RefreshTokenModel;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/auth/login → 200', function () {
    $rf = new Psr17Factory;

    // Employee repo
    $empRepo = Mockery::mock(EmployeeRepositoryInterface::class);
    $empRepo->shouldReceive('findByEmail')
        ->with('alice@example.com')
        ->andReturn(new Employee('E1', 'Alice', 'alice@example.com', '1234567', 100));

    // CredentialsRepository with mocked EM->find/persist/flush
    $emCred = Mockery::mock(\Doctrine\ORM\EntityManagerInterface::class);
    $credModel = new EmployeeCredentialsModel;
    $credModel->employeeId = 'E1';
    $credModel->passwordHash = 'hash';
    $credModel->passwordAlgo = 'argon2id';
    $credModel->status = 1;
    $credModel->updatedAt = new DateTimeImmutable('2025-10-01T00:00:00Z');

    $emCred->shouldReceive('find')
        ->with(EmployeeCredentialsModel::class, 'E1')
        ->andReturn($credModel);
    $emCred->shouldReceive('persist')->byDefault();
    $emCred->shouldReceive('flush')->byDefault();

    $creds = new CredentialsRepository($emCred);

    // RefreshTokenRepository with mocked EM->persist/flush
    $emTok = Mockery::mock(\Doctrine\ORM\EntityManagerInterface::class);
    $emTok->shouldReceive('persist')->with(Mockery::type(RefreshTokenModel::class))->once();
    $emTok->shouldReceive('flush')->once();
    $tokens = new RefreshTokenRepository($emTok);

    // Ports
    $hasher = Mockery::mock(PasswordHasher::class);
    $hasher->shouldReceive('verify')->with('Secret@123', 'hash')->andReturn(true);
    $hasher->shouldReceive('needsRehash')->with('hash')->andReturn(false);

    $jwt = Mockery::mock(JwtIssuer::class);
    $jwt->shouldReceive('issueAccessToken')->andReturn('jwt');

    $clock = Mockery::mock(Clock::class);
    $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2025-10-01T00:00:00Z'));

    $uc = new LoginEmployee($empRepo, $creds, $tokens, $hasher, $jwt, $clock);
    $action = new LoginAction($uc, new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/auth/login');
    $req->getBody()->write(json_encode(['email' => 'alice@example.com', 'password' => 'Secret@123']));
    $req->getBody()->rewind();

    $resp = $action($req);

    expect($resp->getStatusCode())->toBe(200);
    $body = json_decode((string) $resp->getBody(), true);
    expect($body['data'])->toMatchArray([
        'access_token' => 'jwt',
        'expires_in' => 900,
    ]);
    expect($body['data']['refresh_id'])->toBeString();
});
