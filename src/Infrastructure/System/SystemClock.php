<?php

declare(strict_types=1);

namespace Infrastructure\System;

use Application\Port\System\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}
