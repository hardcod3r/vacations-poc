<?php

declare(strict_types=1);

namespace Application\UseCase\Auth;

use Infrastructure\Persistence\Doctrine\Auth\RefreshTokenRepository;
use InvalidArgumentException;

final class Logout
{
    public function __construct(private RefreshTokenRepository $tokens)
    {
    }

    public function execute(string $refreshId): void
    {
        $token = $this->tokens->find($refreshId);

        if ($token === null) {
            throw new InvalidArgumentException('refresh_id not found');
        }
        $this->tokens->revoke($refreshId);
    }
}
