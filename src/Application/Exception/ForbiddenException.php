<?php

declare(strict_types=1);

namespace Application\Exception;

final class ForbiddenException extends \RuntimeException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message);
    }
}
