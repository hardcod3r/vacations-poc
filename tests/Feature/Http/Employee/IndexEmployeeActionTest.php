<?php

declare(strict_types=1);

use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Http\Controllers\Employee\IndexEmployeeAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('GET /api/v1/employees → 200', function () {
    $repo = m::mock(EmployeeRepositoryInterface::class);
    $repo->shouldReceive('all')->andReturn([
        new Employee('3fa85f64-5717-4562-b3fc-2c963f66afa6', 'A', 'a@x.t', 'EC1', 100),
        new Employee('4fa85f64-5717-4562-b3fc-2c963f66afa6', 'B', 'b@x.t', 'EC2', 1),
    ]);

    $rf = new Psr17Factory();
    $resp = (new IndexEmployeeAction($repo, new Responder($rf)))(
        $rf->createServerRequest('GET', '/api/v1/employees')
    );

    expect($resp->getStatusCode())->toBe(200);
    $json = json_decode((string) $resp->getBody(), true);
    expect($json['meta']['count'])->toBe(2);
});
