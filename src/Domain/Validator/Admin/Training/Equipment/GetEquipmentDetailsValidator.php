<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\GetEquipmentDetailsDataInput;
use App\Domain\Validator\DomainValidatorInterface;

final readonly class GetEquipmentDetailsValidator implements DomainValidatorInterface
{
    public function validate(object $input): void
    {
        if (false === $input instanceof GetEquipmentDetailsDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', GetEquipmentDetailsDataInput::class, $input::class));
        }

        // No input-level rule: the id is constrained by the route pattern;
        // EntityNotFoundException is raised by the use case if no row matches.
    }
}
