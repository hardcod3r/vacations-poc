<?php

declare(strict_types=1);

namespace Application\UseCase\Auth;

use Application\Port\Security\JwtIssuer;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Infrastructure\Persistence\Doctrine\Auth\CredentialsRepository;
use Infrastructure\Persistence\Doctrine\Auth\RefreshTokenRepository;
use Infrastructure\Persistence\Doctrine\Model\RefreshTokenModel;

final class LoginEmployee
{
    private const ACCESS_TTL = 900;   // 15'

    private const REFRESH_TTL = 2592000; // 30 days

    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private CredentialsRepository $credentials,
        private RefreshTokenRepository $tokens,
        private PasswordHasher $hasher,
        private JwtIssuer $jwt,
        private Clock $clock,
    ) {
    }

    /** @return array{access_token:string, refresh_id:string, expires_in:int} */
    public function execute(string $email, string $password): array
    {
        $employee = $this->employees->findByEmail($email);

        if (!$employee) {
            throw new \RuntimeException('Invalid credentials');
        }

        $cred = $this->credentials->find($employee->id());

        if (!$cred || $cred->status !== 1) {
            throw new \RuntimeException('Invalid credentials');
        }

        if (!$this->hasher->verify($password, $cred->passwordHash)) {
            throw new \RuntimeException('Invalid credentials');
        }

        if ($this->hasher->needsRehash($cred->passwordHash)) {
            $cred->passwordHash = $this->hasher->hash($password);
            $cred->updatedAt = $this->clock->now();
            $this->credentials->upsert($cred);
        }

        $now = $this->clock->now();
        $refresh = new RefreshTokenModel();
        $refresh->id = self::uuid();
        $refresh->employeeId = $employee->id();
        $refresh->issuedAt = $now;
        $refresh->expiresAt = $now->modify('+' . self::REFRESH_TTL . ' seconds');
        $this->tokens->create($refresh);

        $access = $this->jwt->issueAccessToken([
            'sub' => $employee->id(),
            'role' => $employee->role(),
        ], self::ACCESS_TTL);

        return [
            'access_token' => $access,
            'refresh_id' => $refresh->id,
            'expires_in' => self::ACCESS_TTL,
        ];
    }

    private static function uuid(): string
    {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
}
