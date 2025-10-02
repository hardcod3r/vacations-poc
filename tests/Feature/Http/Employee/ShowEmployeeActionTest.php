<?php

declare(strict_types=1);

use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Http\Controllers\Employee\ShowEmployeeAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('GET /api/v1/employees/{id} → 200', function () {
    $id = '3fa85f64-5717-4562-b3fc-2c963f66afa6';
    $repo = m::mock(EmployeeRepositoryInterface::class);
    $repo->shouldReceive('findById')->with($id)->andReturn(
        new Employee($id, 'A', 'a@x.t', 'EC1', 100),
    );
    $rf = new Psr17Factory();
    $resp = (new ShowEmployeeAction($repo, new Responder($rf)))(
        $rf->createServerRequest('GET', "/api/v1/employees/$id")->withAttribute('id', $id)
    );
    expect($resp->getStatusCode())->toBe(200);
});

it('GET /api/v1/employees/{id} → 404', function () {
    $id = '4fa85f64-5717-4562-b3fc-2c963f66afa6';
    $repo = m::mock(EmployeeRepositoryInterface::class);
    $repo->shouldReceive('findById')->with($id)->andReturn(null);
    $rf = new Psr17Factory();
    $resp = (new ShowEmployeeAction($repo, new Responder($rf)))(
        $rf->createServerRequest('GET', "/api/v1/employees/$id")->withAttribute('id', $id)
    );
    expect($resp->getStatusCode())->toBe(404);
});

it('GET /api/v1/employees/{id} → 422 invalid id', function () {
    $repo = m::mock(EmployeeRepositoryInterface::class);
    $rf = new Psr17Factory();
    $resp = (new ShowEmployeeAction($repo, new Responder($rf)))(
        $rf->createServerRequest('GET', '/api/v1/employees/not-uuid')->withAttribute('id', 'not-uuid')
    );
    expect($resp->getStatusCode())->toBe(422);
});
