<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vacation_requests')]
final class VacationRequestModel
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $id;

    #[ORM\Column(name: 'employee_id', type: 'string', length: 36)]
    public string $employeeId;

    #[ORM\Column(name: 'submitted_at', type: 'datetimetz_immutable')]
    public DateTimeImmutable $submittedAt;

    #[ORM\Column(name: 'from_date', type: 'date_immutable')]
    public DateTimeImmutable $fromDate;

    #[ORM\Column(name: 'to_date', type: 'date_immutable')]
    public DateTimeImmutable $toDate;

    #[ORM\Column(type: 'text')]
    public string $reason;

    #[ORM\Column(type: 'smallint')]
    public int $status;
}
