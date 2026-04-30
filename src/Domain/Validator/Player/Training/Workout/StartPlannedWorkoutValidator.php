<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class StartPlannedWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public function validate(object $input): void
    {
        if (false === $input instanceof StartPlannedWorkoutDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', StartPlannedWorkoutDataInput::class, $input::class));
        }

        // No input-level rule: the use case loads the workout for the logged player
        // (404 on miss) and rejects any status that is not PLANNED.
    }
}
