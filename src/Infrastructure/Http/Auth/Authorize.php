<?php

declare(strict_types=1);

namespace Infrastructure\Http\Auth;

use Domain\Employee\Enum\Role;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Authorize
{
    /** @var int[] */
    public array $roles;

    /**
     * @param  array<int|Role>  $roles
     */
    public function __construct(array $roles)
    {
        // normalise όλα σε int
        $this->roles = \array_map(
            static fn (int|Role $r): int => $r instanceof Role ? $r->value : $r,
            $roles,
        );
    }
}
