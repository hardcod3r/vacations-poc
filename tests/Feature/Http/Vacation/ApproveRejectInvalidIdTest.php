<?php

declare(strict_types=1);

use Application\UseCase\ApproveVacationRequest;
use Application\UseCase\RejectVacationRequest;
use Infrastructure\Http\Controllers\Vacation\ApproveVacationRequestAction;
use Infrastructure\Http\Controllers\Vacation\RejectVacationRequestAction;
use Infrastructure\Http\Responder;
use Nyholm\Psr7\Factory\Psr17Factory;

// 422 on invalid UUID in {id}
it('POST /api/v1/vacations/{id}/approve → 422 invalid id', function () {
    $rf = new Psr17Factory;
    $resp = (new ApproveVacationRequestAction(
        new ApproveVacationRequest(Mockery::mock(\Domain\Vacation\Repository\VacationRequestRepositoryInterface::class)),
        new Responder($rf),
    ))($rf->createServerRequest('POST', '/api/v1/vacations/not-uuid/approve')->withAttribute('id', 'not-uuid'));

    expect($resp->getStatusCode())->toBe(422);
});

it('POST /api/v1/vacations/{id}/reject → 422 invalid id', function () {
    $rf = new Psr17Factory;
    $resp = (new RejectVacationRequestAction(
        new RejectVacationRequest(Mockery::mock(\Domain\Vacation\Repository\VacationRequestRepositoryInterface::class)),
        new Responder($rf),
    ))($rf->createServerRequest('POST', '/api/v1/vacations/not-uuid/reject')->withAttribute('id', 'not-uuid'));

    expect($resp->getStatusCode())->toBe(422);
});
