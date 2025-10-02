<?php

declare(strict_types=1);

namespace Application\UseCase;

use DateTimeImmutable;
use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Ramsey\Uuid\Uuid;

/**
 * Handles the submission of a new vacation request.
 */
final class SubmitVacationRequest
{
    public function __construct(
        private VacationRequestRepositoryInterface $repo,
    ) {
    }

    public function execute(string $employeeId, string $from, string $to, string $reason): VacationRequest
    {
        // Convert strings into proper DateTimeImmutable
        $fromDate = new DateTimeImmutable($from);
        $toDate = new DateTimeImmutable($to);

        $request = new VacationRequest(
            Uuid::uuid4()->toString(),
            $employeeId,
            new DateTimeImmutable('now'),
            $fromDate,
            $toDate,
            $reason,
        );

        $this->repo->save($request);

        return $request;
    }
}
