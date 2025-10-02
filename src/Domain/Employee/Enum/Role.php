<?php

declare(strict_types=1);

namespace Domain\Employee\Enum;

enum Role: int
{
    case Employee = 1;
    case Manager = 100;

    private const LABELS = [
        self::Employee->value => 'employee',
        self::Manager->value => 'manager',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value];
    }

    public static function fromLabel(string $label): self
    {
        $key = \array_search(\strtolower($label), self::LABELS, true);

        if ($key === false) {
            throw new \InvalidArgumentException("Unknown role: $label");
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
