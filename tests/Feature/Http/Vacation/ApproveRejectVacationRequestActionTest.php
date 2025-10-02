<?php

declare(strict_types=1);

use Application\UseCase\ApproveVacationRequest;
use Application\UseCase\RejectVacationRequest;
use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Vacation\ApproveVacationRequestAction;
use Infrastructure\Http\Controllers\Vacation\RejectVacationRequestAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/vacations/{id}/approve → 200', function () {
    $id = '3fa85f64-5717-4562-b3fc-2c963f66afa6';
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with($id)->andReturn(
        new VacationRequest(
            $id,
            'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            'r',
            VacationStatus::Pending,
        ),
    );
    $repo->shouldReceive('save')->once();

    $uc = new ApproveVacationRequest($repo);
    $rf = new Psr17Factory();
    $resp = (new ApproveVacationRequestAction($uc, new Responder($rf)))(
        $rf->createServerRequest('POST', "/api/v1/vacations/$id/approve")->withAttribute('id', $id)
    );
    expect($resp->getStatusCode())->toBe(200);
});

it('POST /api/v1/vacations/{id}/approve → 404', function () {
    $id = '4fa85f64-5717-4562-b3fc-2c963f66afa6';
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with($id)->andReturn(null);

    $uc = new ApproveVacationRequest($repo);
    $rf = new Psr17Factory();
    $resp = (new ApproveVacationRequestAction($uc, new Responder($rf)))(
        $rf->createServerRequest('POST', "/api/v1/vacations/$id/approve")->withAttribute('id', $id)
    );
    expect($resp->getStatusCode())->toBe(404);
});

it('POST /api/v1/vacations/{id}/approve → 409 when not pending', function () {
    $id = '5fa85f64-5717-4562-b3fc-2c963f66afa6';
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with($id)->andReturn(
        new VacationRequest(
            $id,
            'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            'r',
            VacationStatus::Approved,
        ),
    );

    $uc = new ApproveVacationRequest($repo);
    $rf = new Psr17Factory();
    $resp = (new ApproveVacationRequestAction($uc, new Responder($rf)))(
        $rf->createServerRequest('POST', "/api/v1/vacations/$id/approve")->withAttribute('id', $id)
    );
    expect($resp->getStatusCode())->toBe(409);
});

it('POST /api/v1/vacations/{id}/reject → 200', function () {
    $id = '6fa85f64-5717-4562-b3fc-2c963f66afa6';
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findById')->with($id)->andReturn(
        new VacationRequest(
            $id,
            'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            'r',
            VacationStatus::Pending,
        ),
    );
    $repo->shouldReceive('save')->once();

    $uc = new RejectVacationRequest($repo);
    $rf = new Psr17Factory();
    $resp = (new RejectVacationRequestAction($uc, new Responder($rf)))(
        $rf->createServerRequest('POST', "/api/v1/vacations/$id/reject")->withAttribute('id', $id)
    );
    expect($resp->getStatusCode())->toBe(200);
});
