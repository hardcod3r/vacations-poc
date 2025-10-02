<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Employee;

use Application\UseCase\Auth\SetEmployeePassword;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Employee\SetEmployeePasswordRequest;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/employees/{id}/password',
    summary: 'Set Employee Password',
    tags: ['employees'],
    description: 'Set or reset the password for a specific employee by their ID.',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Employee ID',
            schema: new OA\Schema(type: 'string', format: 'uuid'),
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'ok'),
                ],
            ),
        ),
        new OA\Response(
            response: 404,
            description: 'Not Found',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'NOT_FOUND'),
                    new OA\Property(property: 'message', type: 'string', example: 'Employee not found'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Manager->value])]
final class SetEmployeePasswordAction
{
    public function __construct(
        private SetEmployeePassword $useCase,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $idAttr = $req->getAttribute('id');

            if (!\is_string($idAttr)) {
                throw new \InvalidArgumentException('Invalid employee id');
            }
            $employeeId = $idAttr;

            $data = \json_decode((string) $req->getBody(), true);

            if (!\is_array($data)) {
                throw new \InvalidArgumentException('Invalid request body');
            }

            /** @var array{password?: string} $typed */
            $typed = $data;

            $dto = SetEmployeePasswordRequest::fromArray($typed);

            $this->useCase->execute($employeeId, $dto->password);

            return $this->responder->success(['status' => 'ok'], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->responder->error('NOT_FOUND', 'Employee not found', 404);
        }
    }
}
