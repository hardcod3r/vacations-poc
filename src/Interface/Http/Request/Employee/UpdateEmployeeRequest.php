<?php

declare(strict_types=1);

namespace Interface\Http\Request\Employee;

use InvalidArgumentException;
use Respect\Validation\Validator as v;

final class UpdateEmployeeRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $employeeCode,
        public readonly int $role,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (!v::stringType()->notEmpty()->validate($this->name)) {
            throw new InvalidArgumentException('Name is required');
        }

        if (!v::email()->validate($this->email)) {
            throw new InvalidArgumentException('Valid email is required');
        }

        if (!v::digit()->length(7, 7)->validate($this->employeeCode)) {
            throw new InvalidArgumentException('employee_code must be 7 digits');
        }

        if (!v::intType()->between(1, 200)->validate($this->role)) {
            throw new InvalidArgumentException('Role must be valid integer enum');
        }
    }

    /**
     * @param array{
     *     name?: string,
     *     email?: string,
     *     employee_code?: string,
     *     role?: int|string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['employee_code'] ?? '',
            (int) ($data['role'] ?? 0),
        );
    }
}
