<?php

declare(strict_types=1);

namespace Interface\Http\Request\Auth;

use InvalidArgumentException;

final class ChangePasswordRequest
{
    public function __construct(
        public readonly string $old_password,
        public readonly string $new_password,
    ) {
        if ($this->old_password === '') {
            throw new InvalidArgumentException('old_password required');
        }

        if ($this->new_password === '') {
            throw new InvalidArgumentException('new_password required');
        }
    }

    /**
     * @param  array{old_password?: string, new_password?: string}  $in
     */
    public static function fromArray(array $in): self
    {
        return new self(
            (string) ($in['old_password'] ?? ''),
            (string) ($in['new_password'] ?? ''),
        );
    }
}
