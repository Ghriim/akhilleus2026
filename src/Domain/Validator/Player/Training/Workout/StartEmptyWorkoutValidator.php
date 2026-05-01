<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class StartEmptyWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public function validate(StartEmptyWorkoutDataInput $input): void
    {
        // No input-level rule: starting an empty workout requires only an authenticated
        // player, enforced by the firewall.
    }
}
