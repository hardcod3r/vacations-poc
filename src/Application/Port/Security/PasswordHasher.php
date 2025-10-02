<?php

declare(strict_types=1);

namespace Application\Port\Security;

interface PasswordHasher
{
    public function hash(string $plain): string;

    public function verify(string $plain, string $hash): bool;

    public function needsRehash(string $hash): bool;
}
