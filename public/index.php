<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use FastRoute\Dispatcher;
use Infrastructure\Http\Auth\ControllerGuard;
use Infrastructure\Http\Middleware\ErrorHandlerMiddleware;
use Infrastructure\Http\Middleware\RateLimitMiddleware;
use Infrastructure\Http\Responder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var ContainerInterface $container */
$container = require __DIR__.'/../bootstrap/app.php';

/** @var Dispatcher $dispatcher */
$dispatcher = \Interface\Http\Routes::register();

/** @var Psr17Factory $rf */
$rf = $container->get(Psr17Factory::class);

$creator = new ServerRequestCreator($rf, $rf, $rf, $rf);
/** @var ServerRequestInterface $request */
$request = $creator->fromGlobals();

/** @var ErrorHandlerMiddleware $errorHandler */
$errorHandler = $container->get(ErrorHandlerMiddleware::class);

/** @var RateLimitMiddleware $rateLimiter */
$rateLimiter = $container->get(RateLimitMiddleware::class);

/** @var ResponseInterface $response */
$response = $errorHandler($request, function (ServerRequestInterface $req) use ($rateLimiter, $dispatcher, $container, $rf): ResponseInterface {
    return $rateLimiter($req, function (ServerRequestInterface $req2) use ($dispatcher, $container, $rf): ResponseInterface {
        /** @var array{0:int, 1:mixed, 2:array<string,string>} $info */
        $info = $dispatcher->dispatch($req2->getMethod(), rawurldecode($req2->getUri()->getPath()));

        if ($info[0] === Dispatcher::NOT_FOUND) {
            /** @var Responder $responder */
            $responder = $container->get(Responder::class);

            return $responder->error('NOT_FOUND', 'Route not found', 404);
        }

        if ($info[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var Responder $responder */
            $responder = $container->get(Responder::class);

            return $responder->error('METHOD_NOT_ALLOWED', 'Method not allowed', 405);
        }

        /** @var class-string|callable(ServerRequestInterface):ResponseInterface|array{0: class-string|object, 1?: string} $handler */
        $handler = $info[1];
        /** @var array<string,string> $vars */
        $vars = $info[2];

        $guard = new ControllerGuard($container, $rf);

        return $guard->run($req2, $handler, $vars);
    });
});

// ---- Send response ----
\Http\Response\send($response);
