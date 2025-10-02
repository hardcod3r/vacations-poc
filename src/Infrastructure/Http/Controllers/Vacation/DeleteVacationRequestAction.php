<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Vacation;

use Application\UseCase\DeleteOwnVacationRequest;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Vacation\DeleteVacationRequestRequest;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Delete(
    path: '/api/v1/vacations/{id}',
    summary: 'Delete Vacation Request',
    tags: ['vacations'],
    description: 'Delete a vacation request by its ID.',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Vacation Request ID',
            schema: new OA\Schema(type: 'string', format: 'uuid'),
        ),
    ],
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
                    new OA\Property(property: 'message', type: 'string', example: 'Vacation request not found'),
                ],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Validation Error',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'VALIDATION_ERROR'),
                    new OA\Property(property: 'message', type: 'string', example: 'Invalid input data'),
                ],
            ),
        ),
        new OA\Response(
            response: 403,
            description: 'Forbidden',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'FORBIDDEN'),
                    new OA\Property(property: 'message', type: 'string', example: 'You cannot delete this request'),
                ],
            ),
        ),
        new OA\Response(
            response: 409,
            description: 'Conflict',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'CONFLICT'),
                    new OA\Property(property: 'message', type: 'string', example: 'Only pending requests can be deleted'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Employee->value, Role::Manager->value])]
final class DeleteVacationRequestAction
{
    public function __construct(
        private DeleteOwnVacationRequest $useCase,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $idAttr = $req->getAttribute('id');

            if (!\is_string($idAttr)) {
                throw new \InvalidArgumentException('Invalid vacation request id');
            }

            $actorAttr = $req->getAttribute('employee_id');

            if (!\is_string($actorAttr)) {
                throw new \InvalidArgumentException('Invalid employee id');
            }

            $dto = DeleteVacationRequestRequest::fromId($idAttr);
            $actorId = $actorAttr;

            $this->useCase->execute($dto->id, $actorId);

            return $this->responder->success(['status' => 'ok'], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'forbidden') {
                return $this->responder->error('FORBIDDEN', 'You cannot delete this request', 403);
            }

            return $this->responder->error('CONFLICT', 'Only pending requests can be deleted', 409);
        } catch (\RuntimeException $e) {
            return $this->responder->error('NOT_FOUND', 'Vacation request not found', 404);
        }
    }
}
