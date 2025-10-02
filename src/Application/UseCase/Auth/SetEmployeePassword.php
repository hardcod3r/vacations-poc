<?php

declare(strict_types=1);

namespace Application\UseCase\Auth;

use Application\Port\Security\CredentialsStore;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Domain\Employee\Repository\EmployeeRepositoryInterface;

final class SetEmployeePassword
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private CredentialsStore $store,
        private PasswordHasher $hasher,
        private Clock $clock,
    ) {
    }

    public function execute(string $employeeId, string $newPassword): void
    {
        if ($newPassword === '') {
            throw new \InvalidArgumentException('password required');
        }
        $len = \strlen($newPassword) >= 8;
        $L = \preg_match('/[A-Za-z]/', $newPassword);
        $D = \preg_match('/\d/', $newPassword);
        $S = \preg_match('/[^A-Za-z0-9]/', $newPassword);

        if (!($len && $L && $D && $S)) {
            throw new \InvalidArgumentException('weak password');
        }

        if (!$this->employees->findById($employeeId)) {
            throw new \RuntimeException('employee_not_found');
        }

        $hash = $this->hasher->hash($newPassword);
        $this->store->setHash($employeeId, $hash, $this->clock->now());
    }
}
