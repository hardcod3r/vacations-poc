<?php

declare(strict_types=1);

namespace Interface\Http;

use FastRoute\RouteCollector;
use Infrastructure\Http\Controllers\Auth\ChangePasswordAction;
use Infrastructure\Http\Controllers\Auth\LoginAction;
use Infrastructure\Http\Controllers\Auth\LogoutAction;
use Infrastructure\Http\Controllers\Auth\RefreshAction;
use Infrastructure\Http\Controllers\Employee\CreateEmployeeAction;
use Infrastructure\Http\Controllers\Employee\DeleteEmployeeAction;
use Infrastructure\Http\Controllers\Employee\IndexEmployeeAction;
use Infrastructure\Http\Controllers\Employee\SetEmployeePasswordAction;
use Infrastructure\Http\Controllers\Employee\ShowEmployeeAction;
use Infrastructure\Http\Controllers\Employee\UpdateEmployeeAction;
use Infrastructure\Http\Controllers\HealthController;
use Infrastructure\Http\Controllers\Vacation\ApproveVacationRequestAction;
use Infrastructure\Http\Controllers\Vacation\DeleteVacationRequestAction;
use Infrastructure\Http\Controllers\Vacation\ListPendingVacationRequestsAction;
use Infrastructure\Http\Controllers\Vacation\ListVacationRequestsByEmployeeAction;
use Infrastructure\Http\Controllers\Vacation\RejectVacationRequestAction;
use Infrastructure\Http\Controllers\Vacation\SubmitVacationRequestAction;

final class Routes
{
    public static function register(): \FastRoute\Dispatcher
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            $r->addRoute('GET', '/api/v1/employees', IndexEmployeeAction::class);
            $r->addRoute('POST', '/api/v1/employees', CreateEmployeeAction::class);
            $r->addRoute('PUT', '/api/v1/employees/{id}', UpdateEmployeeAction::class);
            $r->addRoute('GET', '/api/v1/employees/{id}', ShowEmployeeAction::class);
            $r->addRoute('DELETE', '/api/v1/employees/{id}', DeleteEmployeeAction::class);

            $r->addRoute('GET', '/api/v1/vacations/pending', ListPendingVacationRequestsAction::class);
            $r->addRoute('POST', '/api/v1/vacations', SubmitVacationRequestAction::class);
            $r->addRoute('POST', '/api/v1/vacations/{id}/approve', ApproveVacationRequestAction::class);
            $r->addRoute('POST', '/api/v1/vacations/{id}/reject', RejectVacationRequestAction::class);
            $r->addRoute('GET', '/api/v1/employees/{id}/vacations', ListVacationRequestsByEmployeeAction::class);
            $r->addRoute('POST', '/api/v1/auth/login', LoginAction::class);
            $r->addRoute('POST', '/api/v1/auth/refresh', RefreshAction::class);
            $r->addRoute('POST', '/api/v1/auth/logout', LogoutAction::class);

            $r->addRoute('POST', '/api/v1/auth/password', ChangePasswordAction::class);
            $r->addRoute('POST', '/api/v1/employees/{id}/password', SetEmployeePasswordAction::class);
            $r->addRoute('DELETE', '/api/v1/vacations/{id}', DeleteVacationRequestAction::class);

            // health check
            $r->addRoute('GET', '/api/v1/health', HealthController::class . '::health');
        });
    }
}
