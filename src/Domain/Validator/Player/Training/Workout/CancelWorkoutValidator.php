<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\CancelWorkoutDataInput;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class CancelWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public function validate(object $input): void
    {
        if (false === $input instanceof CancelWorkoutDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', CancelWorkoutDataInput::class, $input::class));
        }

        // No input-level rule: the use case loads the workout for the logged player
        // (404 on miss) and rejects any status outside {PLANNED, IN_PROGRESS}.
    }
}
