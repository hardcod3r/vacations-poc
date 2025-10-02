<?php

declare(strict_types=1);

namespace Domain\Employee\Entity;

use JsonSerializable;

final class Employee implements JsonSerializable
{
    public function __construct(
        private string $id,
        private string $name,
        private string $email,
        private string $employeeCode,
        private int $role, // tinyint (enum mapping)
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function employeeCode(): string
    {
        return $this->employeeCode;
    }

    public function role(): int
    {
        return $this->role;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'employee_code' => $this->employeeCode,
            'role' => $this->role,
        ];
    }
}
