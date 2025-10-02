<?php

declare(strict_types=1);

namespace Infrastructure\Http\Auth;

final class AuthContext
{
    public function __construct(
        public readonly string $employeeId,
        public readonly int $role,
    ) {
    }
}
