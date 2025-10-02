<?php

declare(strict_types=1);

namespace Application\UseCase;

use Domain\Vacation\Enum\VacationStatus;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;

final class RejectVacationRequest
{
    public function __construct(private VacationRequestRepositoryInterface $repo)
    {
    }

    public function execute(string $requestId): void
    {
        $req = $this->repo->findById($requestId);

        if ($req === null) {
            throw new \RuntimeException('Vacation request not found');
        }

        if ($req->status() !== VacationStatus::Pending) {
            throw new \DomainException('not_pending');
        }

        $req->reject();
        $this->repo->save($req);
    }
}
