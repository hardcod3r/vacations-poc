<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Auth;

use Application\UseCase\Auth\LoginEmployee;
use Infrastructure\Http\Auth\AllowAnonymous;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Auth\LoginRequest;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/auth/login',
    summary: 'Login',
    tags: ['auth'],
    description: 'Authenticate a user and return a JWT token.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'ok'),
                ],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Validation Error',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'VALIDATION_ERROR'),
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid email or password.'),
                ],
            ),
        ),
        new OA\Response(
            response: 401,
            description: 'Unauthorized',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'UNAUTHORIZED'),
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials'),
                ],
            ),
        ),
    ],
)]
#[AllowAnonymous]
final class LoginAction
{
    public function __construct(
        private LoginEmployee $useCase,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $raw = \json_decode((string) $req->getBody(), true);

            if (!\is_array($raw)) {
                throw new \InvalidArgumentException('Invalid JSON payload');
            }

            /** @var array{email?: string, password?: string} $typed */
            $typed = $raw;

            $dto = LoginRequest::fromArray($typed);
            $out = $this->useCase->execute($dto->email, $dto->password);

            return $this->responder->success($out, 200);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->responder->error('UNAUTHORIZED', 'Invalid credentials', 401);
        }
    }
}
