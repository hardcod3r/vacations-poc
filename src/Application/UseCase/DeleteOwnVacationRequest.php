<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;

final class DeleteOwnVacationRequest
{
    public function __construct(private VacationRequestRepositoryInterface $repo)
    {
    }

    public function execute(string $requestId, string $actorEmployeeId): void
    {
        $vr = $this->repo->findById($requestId);

        if (!$vr) {
            throw new \RuntimeException('Vacation request not found');
        }

        if ($vr->employeeId() !== $actorEmployeeId) {
            throw new \DomainException('forbidden');
        }

        if ($vr->status() !== VacationStatus::Pending) {
            throw new \DomainException('not_pending');
        }
        $this->repo->delete($requestId);
    }
}
