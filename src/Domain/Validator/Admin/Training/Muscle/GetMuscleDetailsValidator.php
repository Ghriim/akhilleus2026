<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\GetMuscleDetailsDataInput;
use App\Domain\Validator\DomainValidatorInterface;

final readonly class GetMuscleDetailsValidator implements DomainValidatorInterface
{
    public function validate(object $input): void
    {
        if (false === $input instanceof GetMuscleDetailsDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', GetMuscleDetailsDataInput::class, $input::class));
        }

        // No input-level rule: the id is constrained by the route pattern;
        // EntityNotFoundException is raised by the use case if no row matches.
    }
}
