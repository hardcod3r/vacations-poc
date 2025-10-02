<?php

declare(strict_types=1);

namespace Interface\Http\Request\Vacation;

use InvalidArgumentException;
use Respect\Validation\Validator as v;

final class ListVacationRequestsByEmployeeRequest
{
    public function __construct(public readonly string $employeeId)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (!v::uuid()->validate($this->employeeId)) {
            throw new InvalidArgumentException('Invalid employee_id');
        }
    }

    public static function fromId(string $id): self
    {
        return new self($id);
    }
}
