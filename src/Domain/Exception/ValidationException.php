<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ValidationException extends DomainException
{
    /**
     * @param array<string, list<string>> $violations Map of field path to list of error messages
     */
    public function __construct(
        string $message,
        public readonly array $violations = [],
        public readonly ?string $errorCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
