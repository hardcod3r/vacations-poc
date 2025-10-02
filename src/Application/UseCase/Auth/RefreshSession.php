<?php

declare(strict_types=1);

namespace Application\UseCase\Auth;

use Application\Exception\UnauthorizedException;
use Application\Port\Security\JwtIssuer;
use Application\Port\System\Clock;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Persistence\Doctrine\Auth\RefreshTokenRepository;

final class RefreshSession
{
    private const ACCESS_TTL = 900;      // 15'

    private const REFRESH_TTL = 2592000;  // 30 days

    public function __construct(
        private RefreshTokenRepository $tokens,
        private EmployeeRepositoryInterface $employees,
        private JwtIssuer $jwt,
        private Clock $clock,
    ) {}

    /** @return array{access_token:string, refresh_id:string, expires_in:int} */
    public function execute(string $refreshId): array
    {
        $now = $this->clock->now();

        $t = $this->tokens->find($refreshId);
        if (! $t || $t->revokedAt !== null || $t->expiresAt <= $now) {
            throw new UnauthorizedException('Invalid refresh token');
        }

        $employee = $this->employees->findById($t->employeeId);
        if (! $employee) {
            throw new UnauthorizedException('Invalid refresh token');
        }

        // rotate refresh
        $newId = self::uuid();

        $new = clone $t;
        $new->id = $newId;
        $new->issuedAt = $now;
        $new->expiresAt = $now->modify('+'.self::REFRESH_TTL.' seconds');
        $new->revokedAt = null;
        $new->rotatedTo = null;

        $this->tokens->create($new);        // insert new
        $this->tokens->rotate($t->id, $newId); // link old->new

        // access token
        $access = $this->jwt->issueAccessToken([
            'sub' => $employee->id(),
            'role' => $employee->role(),
        ], self::ACCESS_TTL);

        return [
            'access_token' => $access,
            'refresh_id' => $newId,
            'expires_in' => self::ACCESS_TTL,
        ];
    }

    private static function uuid(): string
    {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
}
