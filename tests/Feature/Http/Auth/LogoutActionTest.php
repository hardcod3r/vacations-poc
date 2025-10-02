<?php

declare(strict_types=1);

use Application\UseCase\Auth\Logout;
use Doctrine\ORM\EntityManagerInterface;
use Infrastructure\Http\Controllers\Auth\LogoutAction;
use Infrastructure\Http\Responder;
use Infrastructure\Persistence\Doctrine\Auth\RefreshTokenRepository;
use Infrastructure\Persistence\Doctrine\Model\RefreshTokenModel;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/auth/logout → 200', function () {
    $rf = new Psr17Factory;

    $em = m::mock(EntityManagerInterface::class);
    $em->shouldReceive('flush')->once();

    $refreshId = '550e8400-e29b-41d4-a716-446655440000'; // valid UUID

    $m = new RefreshTokenModel;
    $m->id = $refreshId;
    $m->employeeId = 'E1';
    $m->issuedAt = new DateTimeImmutable('2025-10-01 12:00:00');
    $m->expiresAt = new DateTimeImmutable('2025-11-01 12:00:00');
    $m->revokedAt = null;
    $m->rotatedTo = null;

    $em->shouldReceive('find')->with(RefreshTokenModel::class, $refreshId)->andReturn($m);

    $tokens = new RefreshTokenRepository($em);
    $uc = new Logout($tokens);
    $action = new LogoutAction($uc, new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/auth/logout')
        ->withHeader('Content-Type', 'application/json');

    $req->getBody()->write(json_encode(['refresh_id' => $refreshId]));
    $req->getBody()->rewind();

    $resp = $action($req);

    expect($resp->getStatusCode())->toBe(200);
    $body = json_decode((string) $resp->getBody(), true);
    expect($body['data']['status'])->toBe('logged_out');
});

it('POST /api/v1/auth/logout → 422 invalid payload', function () {
    $rf = new Psr17Factory;

    $tokens = new RefreshTokenRepository(m::mock(EntityManagerInterface::class));
    $uc = new Logout($tokens);
    $action = new LogoutAction($uc, new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/auth/logout');
    $req->getBody()->write(json_encode(['refresh_id' => '']));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(422);
});
