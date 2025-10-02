<?php

declare(strict_types=1);

namespace Interface\Http\Request\Auth;

use InvalidArgumentException;

final class LoginRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
        if ($this->email === '' || !\filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }

        if ($this->password === '') {
            throw new InvalidArgumentException('Password required');
        }
    }

    /**
     * @param  array{email?: string, password?: string}  $in
     */
    public static function fromArray(array $in): self
    {
        return new self(
            (string) ($in['email'] ?? ''),
            (string) ($in['password'] ?? ''),
        );
    }
}
