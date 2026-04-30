<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class StartEmptyWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public function validate(object $input): void
    {
        if (false === $input instanceof StartEmptyWorkoutDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', StartEmptyWorkoutDataInput::class, $input::class));
        }

        // No input-level rule: starting an empty workout requires only an authenticated player,
        // which is enforced by the firewall + the use case resolving the logged player.
    }
}
