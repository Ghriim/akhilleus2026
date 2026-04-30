<?php

declare(strict_types=1);

namespace App\Domain\Validator;

/**
 * No-op DomainValidator used by UseCases that have no input rule
 * to enforce (typical for List / Get-by-id flows where the gateway
 * fetch already covers the existence concern).
 */
final readonly class EmptyDomainValidator implements DomainValidatorInterface
{
    public function validate(object $input): void
    {
    }
}
