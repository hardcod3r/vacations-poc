<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Application\Port\Security\JwtIssuer;
use Infrastructure\Http\Middleware\AuthenticationMiddleware;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddlewareTest implements RequestHandlerInterface
{
    public function handle(\Psr\Http\Message\ServerRequestInterface $r): \Psr\Http\Message\ResponseInterface
    {
        return new Response(200);
    }
}

\it('401 when missing bearer', function () {
    $jwt = Mockery::mock(JwtIssuer::class);
    $mw = new AuthenticationMiddleware($jwt); // :contentReference[oaicite:1]{index=1}
    $rf = new Psr17Factory();

    $resp = $mw->process($rf->createServerRequest('GET', '/x'), new AuthenticationMiddlewareTest());
    \expect($resp->getStatusCode())->toBe(401);
});

\it('200 when token valid', function () {
    $jwt = Mockery::mock(JwtIssuer::class);
    $jwt->shouldReceive('parseAndVerify')->andReturn(['sub' => 'E1', 'role' => 100]); // :contentReference[oaicite:2]{index=2}
    $mw = new AuthenticationMiddleware($jwt);
    $rf = new Psr17Factory();

    $req = $rf->createServerRequest('GET', '/x')->withHeader('Authorization', 'Bearer abc');
    $resp = $mw->process($req, new AuthenticationMiddlewareTest());
    \expect($resp->getStatusCode())->toBe(200);
});
