<?php

declare(strict_types=1);

use Application\UseCase\UserManagement;
use Domain\Employee\Entity\Employee;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Controllers\Employee\DeleteEmployeeAction;
use Infrastructure\Http\Responder;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;

it('DELETE /api/v1/employees/{id} → 200 with cleanup', function () {
    $id = '3fa85f64-5717-4562-b3fc-2c963f66afa6';
    $v1 = '11111111-1111-4111-8111-111111111111';
    $v2 = '22222222-2222-4222-8222-222222222222';

    $empRepo = m::mock(EmployeeRepositoryInterface::class);
    $vacRepo = m::mock(VacationRequestRepositoryInterface::class);

    // employee exists
    $empRepo->shouldReceive('findById')->with($id)
        ->andReturn(new Employee($id, 'Jane', 'jane@example.com', '1234567', 100));

    // vacations of employee
    $vr1 = new VacationRequest($v1, $id, new DateTimeImmutable(), new DateTimeImmutable(), new DateTimeImmutable(), 'r', VacationStatus::Pending);
    $vr2 = new VacationRequest($v2, $id, new DateTimeImmutable(), new DateTimeImmutable(), new DateTimeImmutable(), 'r', VacationStatus::Approved);

    $vacRepo->shouldReceive('findByEmployee')->with($id)->once()->andReturn([$vr1, $vr2]);
    $vacRepo->shouldReceive('delete')->with($v1)->once();
    $vacRepo->shouldReceive('delete')->with($v2)->once();

    // finally employee delete
    $empRepo->shouldReceive('delete')->with($id)->once();

    $uc = new UserManagement($empRepo, $vacRepo);

    $rf = new Psr17Factory();
    $responder = new Responder($rf);
    $action = new DeleteEmployeeAction($uc, $responder, $rf);

    $req = $rf->createServerRequest('DELETE', "/api/v1/employees/{$id}")
        ->withAttribute('id', $id);

    $resp = $action($req);

    expect($resp->getStatusCode())->toBe(200);
    expect(json_decode((string) $resp->getBody(), true))
        ->toMatchArray(['data' => ['status' => 'ok'], 'meta' => []]);
});
