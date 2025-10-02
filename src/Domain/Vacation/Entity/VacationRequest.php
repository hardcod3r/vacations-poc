<?php

declare(strict_types=1);

namespace Domain\Vacation\Entity;

use DateTimeImmutable;
use Domain\Vacation\Enum\VacationStatus;

/**
 * VacationRequest entity models an employee's vacation request.
 */
final class VacationRequest
{
    public function __construct(
        private string $id,
        private string $employeeId,
        private DateTimeImmutable $submittedAt,
        private DateTimeImmutable $fromDate,
        private DateTimeImmutable $toDate,
        private string $reason,
        private VacationStatus $status = VacationStatus::Pending,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function employeeId(): string
    {
        return $this->employeeId;
    }

    public function submittedAt(): DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function fromDate(): DateTimeImmutable
    {
        return $this->fromDate;
    }

    public function toDate(): DateTimeImmutable
    {
        return $this->toDate;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function status(): VacationStatus
    {
        return $this->status;
    }

    /** Mark as approved */
    public function approve(): void
    {
        $this->status = VacationStatus::Approved;
    }

    /** Mark as rejected */
    public function reject(): void
    {
        $this->status = VacationStatus::Rejected;
    }
}
