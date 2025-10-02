<?php

declare(strict_types=1);

namespace Interface\Http\Request\Employee;

final class SetEmployeePasswordRequest
{
    public function __construct(public readonly string $password)
    {
        if ($this->password === '') {
            throw new \InvalidArgumentException('password required');
        }
    }

    /**
     * @param  array{password?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self((string) ($data['password'] ?? ''));
    }
}
