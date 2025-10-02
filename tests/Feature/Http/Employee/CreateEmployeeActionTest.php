<?php

declare(strict_types=1);

use Application\UseCase\UserManagement;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Employee\CreateEmployeeAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;

it('POST /api/v1/employees → 201', function () {
    $repo = m::mock(EmployeeRepositoryInterface::class);
    $vac = m::mock(VacationRequestRepositoryInterface::class);
    $repo->shouldReceive('save')->once();

    $uc = new UserManagement($repo, $vac);
    $rf = new Psr17Factory;
    $action = new CreateEmployeeAction($uc, new Responder($rf), new Logger('test'));

    $payload = [
        'name' => 'Jane Doe',
        'email' => 'janedoe@example.com',
        'employee_code' => '1234567', // 7 digits
        'role' => 100,                // integer, no string
    ];

    $req = $rf->createServerRequest('POST', '/api/v1/employees');
    $req->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(201);

});

it('POST /api/v1/employees → 409 on duplicate', function () {
    $repo = m::mock(EmployeeRepositoryInterface::class);
    $vac = m::mock(VacationRequestRepositoryInterface::class);

    $ex = new PDOException('unique_violation');
    $ex->errorInfo = ['23505'];

    $repo->shouldReceive('save')->andThrow($ex);

    $uc = new UserManagement($repo, $vac);

    $rf = new Psr17Factory;
    $action = new CreateEmployeeAction($uc, new Responder($rf), new Logger('test'));

    $req = $rf->createServerRequest('POST', '/api/v1/employees');
    $req->getBody()->write(json_encode([
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'employee_code' => '1234567',
        'role' => 100,
    ]));
    $req->getBody()->rewind();

    $resp = $action($req);
    expect($resp->getStatusCode())->toBe(409);
});
