<?php

declare(strict_types=1);

namespace Application\Exception;

final class NotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message);
    }
}
