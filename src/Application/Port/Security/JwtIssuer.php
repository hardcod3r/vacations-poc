<?php

declare(strict_types=1);

namespace Application\Port\Security;

interface JwtIssuer
{
    /** @param array<string,mixed> $claims */
    public function issueAccessToken(array $claims, int $ttlSeconds): string;

    /** @return array<string,mixed> */
    public function parseAndVerify(string $jwt): array;
}
