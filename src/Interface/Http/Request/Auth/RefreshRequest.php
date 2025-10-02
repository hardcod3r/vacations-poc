<?php

declare(strict_types=1);

namespace Interface\Http\Request\Auth;

use InvalidArgumentException;

final class RefreshRequest
{
    public function __construct(public readonly string $refreshId)
    {
        if ($this->refreshId === '') {
            throw new InvalidArgumentException('refresh_id is required');
        }

        if (\strlen($this->refreshId) > 64) {
            throw new InvalidArgumentException('refresh_id too long');
        }

        // Check if it's a valid UUID
        if (!\preg_match('/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[1-5][0-9a-fA-F]{3}\-[89abAB][0-9a-fA-F]{3}\-[0-9a-fA-F]{12}$/', $this->refreshId)) {
            throw new InvalidArgumentException('Invalid refresh_id format (expected UUID)');
        }
    }

    /**
     * @param  array{refresh_id?: string}  $in
     */
    public static function fromArray(array $in): self
    {
        return new self((string) ($in['refresh_id'] ?? ''));
    }
}
