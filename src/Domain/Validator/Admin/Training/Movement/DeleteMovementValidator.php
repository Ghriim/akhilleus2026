<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\DeleteMovementDataInput;
use App\Domain\Validator\AbstractLoggedUserValidator;

final readonly class DeleteMovementValidator extends AbstractLoggedUserValidator
{
    public function validate(object $input): void
    {
        if (false === $input instanceof DeleteMovementDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeleteMovementDataInput::class, $input::class));
        }

        // No input-level rule: the use case fetches the entity and throws
        // EntityNotFoundException if the id does not match a row.
    }
}
