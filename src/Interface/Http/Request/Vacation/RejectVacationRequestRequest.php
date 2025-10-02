<?php

declare(strict_types=1);

namespace Interface\Http\Request\Vacation;

use InvalidArgumentException;
use Respect\Validation\Validator as v;

final class RejectVacationRequestRequest
{
    public function __construct(public readonly string $id)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (!v::uuid()->validate($this->id)) {
            throw new InvalidArgumentException('Invalid id');
        }
    }

    public static function fromId(string $id): self
    {
        return new self($id);
    }
}
