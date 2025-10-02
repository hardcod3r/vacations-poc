<?php

declare(strict_types=1);

namespace Application\Exception;

final class UnauthorizedException extends \RuntimeException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message);
    }
}
