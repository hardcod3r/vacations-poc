<?php

declare(strict_types=1);

namespace Application\Exception;

class ValidationException extends \RuntimeException
{
    public function __construct(
        string $message,
        /** @var array<string,string> */
        private array $details = [

        ],
    ) {
        parent::__construct($message, 422);
    }

    /** @return array<string, string> */
    public function details(): array
    {
        return $this->details;
    }
}
