<?php

declare(strict_types=1);

use Infrastructure\Http\Controllers\HealthController;
use Infrastructure\Http\Responder;
use Nyholm\Psr7\Factory\Psr17Factory;

it('GET /health → 200 envelope ok', function () {
    $rf = new Psr17Factory;
    $responder = new Responder($rf);
    $action = new HealthController($responder);

    $req = $rf->createServerRequest('GET', '/api/v1/health');

    // Call the action as a function
    $resp = $action->health($req);

    expect($resp->getStatusCode())->toBe(200);
    expect(json_decode((string) $resp->getBody(), true))
        ->toMatchArray(['data' => ['status' => 'ok'], 'meta' => []]);
});
