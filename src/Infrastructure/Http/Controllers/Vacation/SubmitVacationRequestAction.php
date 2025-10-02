<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers\Vacation;

use Application\UseCase\SubmitVacationRequest;
use Domain\Employee\Enum\Role;
use Infrastructure\Http\Auth\Authorize;
use Infrastructure\Http\Responder;
use Interface\Http\Request\Vacation\SubmitVacationRequestRequest;
use Interface\Http\Response\VacationRequestResource;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/api/v1/vacations',
    summary: 'Submit a Vacation Request',
    tags: ['vacations'],
    description: 'Submit a new vacation request for an employee.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'employee_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'from', type: 'string', format: 'date'),
                new OA\Property(property: 'to', type: 'string', format: 'date'),
                new OA\Property(property: 'reason', type: 'string'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Created',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/VacationRequestResource'),
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
                    new OA\Property(property: 'message', type: 'string', example: 'Employee not found'),
                ],
            ),
        ),
        new OA\Response(
            response: 409,
            description: 'Conflict',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'CONFLICT'),
                    new OA\Property(property: 'message', type: 'string', example: 'Overlapping vacation dates'),
                ],
            ),
        ),
    ],
)]
#[Authorize([Role::Employee->value, Role::Manager->value])]
final class SubmitVacationRequestAction
{
    public function __construct(
        private SubmitVacationRequest $useCase,
        private Responder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        try {
            $req->getBody()->rewind();
            $decoded = json_decode((string) $req->getBody(), true);

            /** @var array<string,mixed> $data */
            $data = is_array($decoded) ? $decoded : [];

            /** @var array{employee_id?: string, from?: string, to?: string, reason?: string} $typed */
            $typed = [
                ...(isset($data['employee_id']) && is_string($data['employee_id']) ? ['employee_id' => $data['employee_id']] : []),
                ...(isset($data['from']) && is_string($data['from']) ? ['from' => $data['from']] : []),
                ...(isset($data['to']) && is_string($data['to']) ? ['to' => $data['to']] : []),
                ...(isset($data['reason']) && is_string($data['reason']) ? ['reason' => $data['reason']] : []),
            ];

            $dto = SubmitVacationRequestRequest::fromArray($typed);

            $vr = $this->useCase->execute(
                $dto->employeeId,
                $dto->from,
                $dto->to,
                $dto->reason,
            );

            return $this->responder->success(new VacationRequestResource($vr), 201);

        } catch (\InvalidArgumentException $e) {
            return $this->responder->error('VALIDATION_ERROR', $e->getMessage(), 422);

        } catch (\Doctrine\DBAL\Exception\DriverException $e) {
            $sqlState = $e->getSQLState();
            if ($sqlState === '23503') {
                return $this->responder->error('NOT_FOUND', 'Employee not found', 404);
            }
            if ($sqlState === '23P01') {
                return $this->responder->error('CONFLICT', 'Overlapping vacation dates', 409);
            }
            throw $e;
        } catch (\PDOException $e) {
            $sqlState = $e->errorInfo[0] ?? (string) $e->getCode();
            if ($sqlState === '23503') {
                return $this->responder->error('NOT_FOUND', 'Employee not found', 404);
            }
            if ($sqlState === '23P01') {
                return $this->responder->error('CONFLICT', 'Overlapping vacation dates', 409);
            }
            throw $e;
        }
    }
}
