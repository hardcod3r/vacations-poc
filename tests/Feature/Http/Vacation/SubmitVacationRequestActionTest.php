<?php

declare(strict_types=1);

use Application\UseCase\SubmitVacationRequest;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Vacation\SubmitVacationRequestAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/vacations → 201', function () {
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('save')->once();

    $uc = new SubmitVacationRequest($repo);
    $rf = new Psr17Factory();
    $action = new SubmitVacationRequestAction($uc, new Responder($rf));

    $payload = [
        'employee_id' => 'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef',
        'from' => '2025-01-10',
        'to' => '2025-01-12',
        'reason' => 'Family',
    ];

    $req = $rf->createServerRequest('POST', '/api/v1/vacations');
    $req->getBody()->write(json_encode($payload));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(201);
});

it('POST /api/v1/vacations → 422 invalid dto', function () {
    $uc = new SubmitVacationRequest(m::mock(VacationRequestRepositoryInterface::class));
    $rf = new Psr17Factory();
    $action = new SubmitVacationRequestAction($uc, new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/vacations');
    $req->getBody()->write(json_encode([
        'employee_id' => 'not-uuid', 'from' => '2025/01/10', 'to' => '2025-01-09', 'reason' => '',
    ]));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(422);
});

it('POST /api/v1/vacations → 404 on FK violation', function () {
    $ex = new PDOException('fk_violation');
    $ex->errorInfo = ['23503'];

    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('save')->andThrow($ex);

    $uc = new SubmitVacationRequest($repo);
    $rf = new Psr17Factory();
    $action = new SubmitVacationRequestAction($uc, new Responder($rf));

    $req = $rf->createServerRequest('POST', '/api/v1/vacations');
    $req->getBody()->write(json_encode([
        'employee_id' => 'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef',
        'from' => '2025-01-10', 'to' => '2025-01-12', 'reason' => 'Family',
    ]));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(404);
});
