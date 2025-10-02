<?php

declare(strict_types=1);

namespace Interface\Http\Request\Vacation;

use InvalidArgumentException;
use Respect\Validation\Validator as v;

final class SubmitVacationRequestRequest
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $from,
        public readonly string $to,
        public readonly string $reason,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (!v::uuid()->validate($this->employeeId)) {
            throw new InvalidArgumentException('Invalid employee_id');
        }

        if (!v::date('Y-m-d')->validate($this->from)) {
            throw new InvalidArgumentException('from must be Y-m-d');
        }

        if (!v::date('Y-m-d')->validate($this->to)) {
            throw new InvalidArgumentException('to must be Y-m-d');
        }

        if (\strtotime($this->from) > \strtotime($this->to)) {
            throw new InvalidArgumentException('from must be <= to');
        }

        if (!v::stringType()->notEmpty()->length(1, 2000)->validate($this->reason)) {
            throw new InvalidArgumentException('reason required');
        }
    }

    /**
     * @param array{
     *     employee_id?: string,
     *     from?: string,
     *     to?: string,
     *     reason?: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['employee_id'] ?? '',
            $data['from'] ?? '',
            $data['to'] ?? '',
            $data['reason'] ?? '',
        );
    }
}
