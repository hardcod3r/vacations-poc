<?php

declare(strict_types=1);

namespace Interface\Http\Response;

use Domain\Vacation\Entity\VacationRequest;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'VacationRequestResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'employee_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'from_date', type: 'string', format: 'date'),
        new OA\Property(property: 'to_date', type: 'string', format: 'date'),
        new OA\Property(property: 'reason', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected']),
    ],
)]
final class VacationRequestResource implements JsonSerializable
{
    public function __construct(private VacationRequest $request)
    {
    }

    /**
     * @return array{
     *     id: string,
     *     employee_id: string,
     *     submitted_at: string,
     *     from_date: string,
     *     to_date: string,
     *     reason: string|null,
     *     status: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->request->id(),
            'employee_id' => $this->request->employeeId(),
            'submitted_at' => $this->request->submittedAt()->format('c'),
            'from_date' => $this->request->fromDate()->format('Y-m-d'),
            'to_date' => $this->request->toDate()->format('Y-m-d'),
            'reason' => $this->request->reason(),
            'status' => $this->request->status()->label(), // human readable
        ];
    }
}
