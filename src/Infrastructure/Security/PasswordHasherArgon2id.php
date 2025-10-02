<?php

declare(strict_types=1);

namespace Infrastructure\Security;

use Application\Port\Security\PasswordHasher;

final class PasswordHasherArgon2id implements PasswordHasher
{
    /**
     * @var array{memory_cost: int, time_cost: int, threads: int}
     */
    private array $opts;

    public function __construct(
        int $memoryCost = 1 << 17, // 128MB
        int $timeCost = 4,
        int $threads = 2,
    ) {
        $this->opts = [
            'memory_cost' => $memoryCost,
            'time_cost' => $timeCost,
            'threads' => $threads,
        ];
    }

    public function hash(string $plain): string
    {
        return \password_hash($plain, PASSWORD_ARGON2ID, $this->opts);
    }

    public function verify(string $plain, string $hash): bool
    {
        return \password_verify($plain, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return \password_needs_rehash($hash, PASSWORD_ARGON2ID, $this->opts);
    }
}
