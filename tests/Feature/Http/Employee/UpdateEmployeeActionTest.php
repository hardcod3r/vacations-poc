<?php

declare(strict_types=1);

use Application\UseCase\UserManagement;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Employee\UpdateEmployeeAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('PUT /api/v1/employees/{id} → 200', function () {
    $id = '3fa85f64-5717-4562-b3fc-2c963f66afa6';
    $repo = m::mock(EmployeeRepositoryInterface::class);
    $vac = m::mock(VacationRequestRepositoryInterface::class);

    $repo->shouldReceive('findById')->with($id)
        ->andReturn(new Employee($id, 'A', 'a@x.t', '1234567', 100));
    $repo->shouldReceive('save')->once();
    $repo->shouldReceive('findById')->with($id)
        ->andReturn(new Employee($id, 'A', 'a@x.t', '1234567', 100));

    $uc = new UserManagement($repo, $vac);

    $rf = new Psr17Factory();
    $action = new UpdateEmployeeAction($uc, new Responder($rf));

    $req = $rf->createServerRequest('PUT', "/api/v1/employees/$id")->withAttribute('id', $id);
    $req->getBody()->write(json_encode([
        'name' => 'A',
        'email' => 'a@x.t',
        'employee_code' => '1234567',
        'role' => 100,
    ]));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(200);
});
