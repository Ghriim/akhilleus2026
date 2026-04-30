<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\GetMovementDetailsDataInput;
use App\Domain\Validator\DomainValidatorInterface;

final readonly class GetMovementDetailsValidator implements DomainValidatorInterface
{
    public function validate(object $input): void
    {
        if (false === $input instanceof GetMovementDetailsDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', GetMovementDetailsDataInput::class, $input::class));
        }

        // No input-level rule: the id is constrained by the route pattern;
        // EntityNotFoundException is raised by the use case if no row matches.
    }
}
