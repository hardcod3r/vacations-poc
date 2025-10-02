<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Vacation;

use Domain\Employee\Enum\Role;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Vacation\ListVacationRequestsByEmployeeRequest;
use Interface\Http\Response\VacationRequestResource;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Get(
    path: '/api/v1/employees/{id}/vacations',
    summary: 'List Vacation Requests by Employee',
    description: 'List all vacation requests for a specific employee by their ID.',
    tags: ['vacations'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Employee ID',
            schema: new OA\Schema(type: 'string', format: 'uuid'),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/VacationRequestResource'),
                    ),
                    new OA\Property(
                        property: 'meta',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'count', type: 'integer', example: 2),
                        ],
                    ),
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
    ],
)]
#[Authorize([Role::Employee->value, Role::Manager->value])]
final class ListVacationRequestsByEmployeeAction
{
    public function __construct(
        private VacationRequestRepositoryInterface $repo,
        private Responder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $idAttr = $req->getAttribute('id');

            if (! \is_string($idAttr)) {
                throw new \InvalidArgumentException('Invalid employee id');
            }

            $dto = ListVacationRequestsByEmployeeRequest::fromId($idAttr);
            $items = $this->repo->findByEmployee($dto->employeeId);
            $resources = \array_map(
                fn ($r) => new VacationRequestResource($r),
                $items,
            );

            return $this->responder->success($resources, 200, [
                'count' => \count($resources),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
