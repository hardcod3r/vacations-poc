<?php

declare(strict_types=1);

namespace Domain\Vacation\Enum;

enum VacationStatus: int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;

    private const LABELS = [
        self::Pending->value => 'pending',
        self::Approved->value => 'approved',
        self::Rejected->value => 'rejected',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value];
    }

    public static function fromLabel(string $label): self
    {
        $key = \array_search(\strtolower($label), self::LABELS, true);

        if ($key === false) {
            throw new \InvalidArgumentException("Unknown status: $label");
        }

        return self::from($key);
    }

    /** @return int[] */
    public static function all(): array
    {
        return \array_map(fn (self $c) => $c->value, self::cases());
    }

    /** @return string[] */
    public static function allLabels(): array
    {
        return \array_map(fn (self $c) => $c->label(), self::cases());
    }

    /** @return array<int,string> */
    public static function map(): array
    {
        return self::LABELS;
    }
}
