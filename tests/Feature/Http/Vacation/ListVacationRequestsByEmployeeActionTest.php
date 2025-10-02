<?php

declare(strict_types=1);

use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Vacation\ListVacationRequestsByEmployeeAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('GET /api/v1/employees/{id}/vacations → 200', function () {
    $eid = 'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef';

    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findByEmployee')->with($eid)->andReturn([
        new VacationRequest(
            '3fa85f64-5717-4562-b3fc-2c963f66afa6',
            $eid,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-02-01'),
            new DateTimeImmutable('2025-02-03'),
            'reason',
            VacationStatus::Pending,
        ),
        new VacationRequest(
            '4fa85f64-5717-4562-b3fc-2c963f66afa6',
            $eid,
            new DateTimeImmutable('2025-01-02'),
            new DateTimeImmutable('2025-02-10'),
            new DateTimeImmutable('2025-02-12'),
            'reason',
            VacationStatus::Approved,
        ),
    ]);

    $rf = new Psr17Factory();
    $resp = (new ListVacationRequestsByEmployeeAction($repo, new Responder($rf)))(
        $rf->createServerRequest('GET', "/api/v1/employees/$eid/vacations")->withAttribute('id', $eid)
    );

    expect($resp->getStatusCode())->toBe(200);
    $body = json_decode((string) $resp->getBody(), true);
    expect($body['meta']['count'])->toBe(2);
});

it('GET /api/v1/employees/{id}/vacations → 422 invalid id', function () {
    $rf = new Psr17Factory();
    $resp = (new ListVacationRequestsByEmployeeAction(
        m::mock(VacationRequestRepositoryInterface::class),
        new Responder($rf),
    ))($rf->createServerRequest('GET', '/api/v1/employees/not-uuid/vacations')->withAttribute('id', 'not-uuid'));
    expect($resp->getStatusCode())->toBe(422);
});
