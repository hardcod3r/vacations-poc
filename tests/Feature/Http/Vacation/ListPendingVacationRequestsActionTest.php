<?php

declare(strict_types=1);

use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Vacation\ListPendingVacationRequestsAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('GET /api/v1/vacations/pending → 200', function () {
    $repo = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('findPending')->andReturn([
        new VacationRequest(
            '3fa85f64-5717-4562-b3fc-2c963f66afa6',
            'e1eb134a-1cc3-4d1a-b2f3-59d7b1a0a9ef',
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-02-01'),
            new DateTimeImmutable('2025-02-03'),
            'reason',
            VacationStatus::Pending,
        ),
    ]);

    $rf = new Psr17Factory();
    $resp = (new ListPendingVacationRequestsAction($repo, new Responder($rf)))(
        $rf->createServerRequest('GET', '/api/v1/vacations/pending')
    );

    expect($resp->getStatusCode())->toBe(200);
    expect(json_decode((string) $resp->getBody(), true)['meta']['count'])->toBe(1);
});
