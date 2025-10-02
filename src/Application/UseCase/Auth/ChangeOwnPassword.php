<?php

declare(strict_types=1);

namespace Application\UseCase\Auth;

use Application\Exception\ValidationException;
use Application\Port\Security\CredentialsStore;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;

/**
 * ChangeOwnPassword.
 */
final class ChangeOwnPassword
{
    public function __construct(
        private CredentialsStore $store,
        private PasswordHasher $hasher,
        private Clock $clock,
    ) {
    }

    public function execute(string $employeeId, string $old, string $new): void
    {
        $errors = [];

        if ($old === '') {
            $errors['old_password'] = 'required';
        }

        if ($new === '') {
            $errors['new_password'] = 'required';
        }

        if ($new !== '') {
            $len = \strlen($new) >= 8;
            $L = \preg_match('/[A-Za-z]/', $new);
            $D = \preg_match('/\d/', $new);
            $S = \preg_match('/[^A-Za-z0-9]/', $new);

            if (!($len && $L && $D && $S)) {
                $errors['new_password'] = 'weak_password';
            }
        }

        if ($errors) {
            throw new ValidationException('VALIDATION_ERROR', $errors);
        }

        $currentHash = $this->store->getHash($employeeId);

        if ($currentHash === null) {
            throw new ValidationException('VALIDATION_ERROR', ['old_password' => 'credentials_missing']);
        }

        if (!$this->hasher->verify($old, $currentHash)) {
            throw new ValidationException('VALIDATION_ERROR', ['old_password' => 'invalid_old_password']);
        }

        if ($this->hasher->verify($new, $currentHash)) {
            throw new ValidationException('VALIDATION_ERROR', ['new_password' => 'same_as_old']);
        }

        $newHash = $this->hasher->hash($new);
        $this->store->setHash($employeeId, $newHash, $this->clock->now());
    }
}
