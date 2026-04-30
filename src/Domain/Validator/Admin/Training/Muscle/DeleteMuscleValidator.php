<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\DeleteMuscleDataInput;
use App\Domain\Validator\AbstractLoggedUserValidator;

final readonly class DeleteMuscleValidator extends AbstractLoggedUserValidator
{
    public function validate(object $input): void
    {
        if (false === $input instanceof DeleteMuscleDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeleteMuscleDataInput::class, $input::class));
        }

        // No input-level rule: the use case fetches the entity and throws
        // EntityNotFoundException if the id does not match a row.
    }
}
