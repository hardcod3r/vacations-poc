<?php

declare(strict_types=1);

namespace Interface\Http\Request\Vacation;

final class ListPendingVacationRequestsRequest
{
    public static function fromGlobals(): self
    {
        return new self();
    }
}
