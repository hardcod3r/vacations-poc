<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Vacation;

use Application\UseCase\RejectVacationRequest;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Vacation\RejectVacationRequestRequest;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/vacations/{id}/reject',
    summary: 'Reject a Vacation Request',
    description: 'Reject a pending vacation request by its ID.',
    tags: ['vacations'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Vacation Request ID',
            schema: new OA\Schema(type: 'string'),
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
        new OA\Response(response: 401, description: 'Unauthorized'),
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
            response: 409,
            description: 'Conflict',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'CONFLICT'),
                    new OA\Property(property: 'message', type: 'string', example: 'Only pending requests can be rejected'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Manager->value])]
final class RejectVacationRequestAction
{
    public function __construct(
        private RejectVacationRequest $useCase,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $idAttr = $req->getAttribute('id');

            if (!\is_string($idAttr)) {
                throw new \InvalidArgumentException('Invalid vacation request ID');
            }

            $dto = RejectVacationRequestRequest::fromId($idAttr);

            $this->useCase->execute($dto->id);

            return $this->responder->success(['status' => 'ok'], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'not_pending') {
                return $this->responder->error('CONFLICT', 'Only pending requests can be rejected', 409);
            }

            return $this->responder->error('DOMAIN_ERROR', $e->getMessage(), 409);
        } catch (\RuntimeException $e) {
            return $this->responder->error('NOT_FOUND', 'Vacation request not found', 404);
        }
    }
}
