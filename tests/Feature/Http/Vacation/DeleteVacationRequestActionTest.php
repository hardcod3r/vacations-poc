<?php

declare(strict_types=1);

use Application\UseCase\DeleteOwnVacationRequest;
use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Vacation\DeleteVacationRequestAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('DELETE /api/v1/vacations/{id} → 200', function () {
    $id = '3fa85f64-5717-4562-b3fc-2c963f66afa6'; // v4
    $eid = 'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef';  // v4

    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $vr = new VacationRequest(
        $id,
        $eid,
        new DateTimeImmutable('2025-01-01'),
        new DateTimeImmutable('2025-01-10'),
        new DateTimeImmutable('2025-01-12'),
        'reason',
        VacationStatus::Pending,
    );
    $repo->shouldReceive('findById')->with($id)->andReturn($vr);
    $repo->shouldReceive('delete')->with($id)->once();

    $uc = new DeleteOwnVacationRequest($repo);

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new DeleteVacationRequestAction($uc, $responder);

    $req = $rf->createServerRequest('DELETE', "/api/v1/vacations/{$id}")
        ->withAttribute('id', $id)
        ->withAttribute('employee_id', $eid);

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(200);
    expect(json_decode((string) $resp->getBody(), true))
        ->toMatchArray(['data' => ['status' => 'ok'], 'meta' => []]);
});

it('DELETE /api/v1/vacations/{id} → 403 when forbidden', function () {
    $id = '6fa85f64-5717-4562-b3fc-2c963f66afa6'; // v4
    $eid = 'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef'; // actor
    $owner = '2dc6b3a2-0c3a-4f9f-a8e7-6f3c0a6f2a11'; // different owner

    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $vr = new VacationRequest(
        $id,
        $owner,
        new DateTimeImmutable('2025-01-01'),
        new DateTimeImmutable('2025-01-10'),
        new DateTimeImmutable('2025-01-12'),
        'reason',
        VacationStatus::Pending,
    );
    $repo->shouldReceive('findById')->with($id)->andReturn($vr);

    $uc = new DeleteOwnVacationRequest($repo);

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new DeleteVacationRequestAction($uc, $responder);

    $req = $rf->createServerRequest('DELETE', "/api/v1/vacations/{$id}")
        ->withAttribute('id', $id)
        ->withAttribute('employee_id', $eid);

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(403);
});

it('DELETE /api/v1/vacations/{id} → 404 when not found', function () {
    $id = '9fa85f64-5717-4562-b3fc-2c963f66afa6';
    $eid = 'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef';

    $repo = Mockery::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with($id)->andReturn(null);

    $uc = new DeleteOwnVacationRequest($repo);

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new DeleteVacationRequestAction($uc, $responder);

    $req = $rf->createServerRequest('DELETE', "/api/v1/vacations/{$id}")
        ->withAttribute('id', $id)
        ->withAttribute('employee_id', $eid);

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(404);
});
