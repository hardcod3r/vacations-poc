<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine;

use Domain\Vacation\Entity\VacationRequest;
use Domain\Vacation\Enum\VacationStatus;
use Infrastructure\Persistence\Doctrine\Model\VacationRequestModel;

final class VacationRequestMapper
{
    public static function toDomain(VacationRequestModel $m): VacationRequest
    {
        return new VacationRequest(
            $m->id,
            $m->employeeId,
            $m->submittedAt,
            $m->fromDate,
            $m->toDate,
            $m->reason,
            VacationStatus::from($m->status),
        );
    }

    public static function toModel(VacationRequest $e, ?VacationRequestModel $m = null): VacationRequestModel
    {
        $m ??= new VacationRequestModel();
        $m->id = $e->id();
        $m->employeeId = $e->employeeId();
        $m->submittedAt = $e->submittedAt();
        $m->fromDate = $e->fromDate();
        $m->toDate = $e->toDate();
        $m->reason = $e->reason();
        $m->status = $e->status()->value;

        return $m;
    }
}
