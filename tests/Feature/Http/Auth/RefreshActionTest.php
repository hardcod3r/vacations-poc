<?php

declare(strict_types=1);

use Application\Port\Security\JwtIssuer;
use Application\Port\System\Clock;
use Application\UseCase\Auth\RefreshSession;
use Doctrine\ORM\EntityManagerInterface;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Http\Controllers\Auth\RefreshAction;
use Infrastructure\Http\Responder;
use Infrastructure\Persistence\Doctrine\Auth\RefreshTokenRepository;
use Infrastructure\Persistence\Doctrine\Model\RefreshTokenModel;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/auth/refresh → 200', function () {
    $rf = new Psr17Factory;

    $em = m::mock(EntityManagerInterface::class);
    $em->shouldReceive('persist')->byDefault();
    $em->shouldReceive('flush')->byDefault();

    $refreshId = '550e8400-e29b-41d4-a716-446655440000';

    $rt = new RefreshTokenModel;
    $rt->id = $refreshId;
    $rt->employeeId = 'EID-123';
    $rt->issuedAt = new DateTimeImmutable('2025-10-01 12:00:00');
    $rt->expiresAt = new DateTimeImmutable('2025-11-01 12:00:00');
    $rt->revokedAt = null;
    $rt->rotatedTo = null;

    $em->shouldReceive('find')
        ->with(RefreshTokenModel::class, $refreshId)
        ->andReturn($rt)
        ->atLeast()->once();

    $tokens = new RefreshTokenRepository($em);

    $employees = m::mock(EmployeeRepositoryInterface::class);
    $employees->shouldReceive('findById')
        ->with('EID-123')
        ->andReturn(new Employee('EID-123', 'John', 'john@example.com', '1234567', 1));

    $jwt = m::mock(JwtIssuer::class);
    $jwt->shouldReceive('issueAccessToken')->andReturn('AT.jwt');

    $clock = m::mock(Clock::class);
    $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2025-10-01 12:00:00'));

    $uc = new RefreshSession($tokens, $employees, $jwt, $clock);
    $action = new RefreshAction($uc, new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/auth/refresh')
        ->withHeader('Content-Type', 'application/json');

    $req->getBody()->write(json_encode(['refresh_id' => $refreshId]));
    $req->getBody()->rewind();

    $resp = $action($req);

    expect($resp->getStatusCode())->toBe(200);
    $body = json_decode((string) $resp->getBody(), true);
    expect($body['data'])->toHaveKeys(['access_token', 'refresh_id', 'expires_in']);
    expect($body['data']['access_token'])->toBe('AT.jwt');
    expect($body['data']['expires_in'])->toBe(900);  // :contentReference[oaicite:9]{index=9}
});

it('POST /api/v1/auth/refresh → 401 on invalid token', function () {
    $rf = new Psr17Factory;

    // valid UUID v4 + valid variant (starts with 'a')
    $invalid = '550e8400-e29b-41d4-a716-446655440999';

    $em = m::mock(EntityManagerInterface::class);
    $em->shouldReceive('find')
        ->with(RefreshTokenModel::class, $invalid)
        ->andReturn(null); // not found -> Unauthorized

    $tokens = new RefreshTokenRepository($em);
    $employees = m::mock(EmployeeRepositoryInterface::class);
    $jwt = m::mock(JwtIssuer::class);
    $clock = m::mock(Clock::class);
    $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2025-10-01 12:00:00'));

    $action = new RefreshAction(new RefreshSession($tokens, $employees, $jwt, $clock), new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/auth/refresh')
        ->withHeader('Content-Type', 'application/json');

    $req->getBody()->write(json_encode(['refresh_id' => $invalid]));
    $req->getBody()->rewind();

    $resp = $action($req);

    expect($resp->getStatusCode())->toBe(401);
});

it('POST /api/v1/auth/refresh → 422 on bad payload', function () {
    $rf = new Psr17Factory;

    $tokens = new RefreshTokenRepository(m::mock(EntityManagerInterface::class));
    $employees = m::mock(EmployeeRepositoryInterface::class);
    $jwt = m::mock(JwtIssuer::class);
    $clock = m::mock(Clock::class);

    $action = new RefreshAction(new RefreshSession($tokens, $employees, $jwt, $clock), new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/auth/refresh')
        ->withHeader('Content-Type', 'application/json');

    $req->getBody()->write(json_encode(['refresh_id' => '']));
    $req->getBody()->rewind();

    $resp = $action($req);

    expect($resp->getStatusCode())->toBe(422);
});
