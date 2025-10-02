<?php

declare(strict_types=1);

namespace Interface\Http\Request\Employee;

use InvalidArgumentException;
use Respect\Validation\Validator as v;

final class ShowEmployeeRequest
{
    public function __construct(
        public readonly string $id,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (!v::uuid()->validate($this->id)) {
            throw new InvalidArgumentException('Invalid employee ID');
        }
    }

    public static function fromId(string $id): self
    {
        return new self($id);
    }
}
