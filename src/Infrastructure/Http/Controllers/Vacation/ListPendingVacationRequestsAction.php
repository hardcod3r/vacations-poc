<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Vacation;

use Domain\Employee\Enum\Role;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Response\VacationRequestResource;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Get(
    path: '/api/v1/vacations/pending',
    summary: 'List Pending Vacation Requests',
    tags: ['vacations'],
    description: 'Retrieve a list of all pending vacation requests.',
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
    ],
)]

#[Authorize([Role::Manager->value])]
final class ListPendingVacationRequestsAction
{
    public function __construct(
        private VacationRequestRepositoryInterface $repo,
        private Responder $responder,
    ) {
    }

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        $pending = $this->repo->findPending();

        $resources = \array_map(
            fn ($r) => new VacationRequestResource($r),
            $pending,
        );

        return $this->responder->success($resources, 200, [
            'count' => \count($resources),
        ]);
    }
}
