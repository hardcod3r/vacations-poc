<?php

declare(strict_types=1);

namespace Application\Port\System;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
