<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Training\Exercise;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class AddMovementToWorkoutDataInput implements DataInputInterface
{
    public function __construct(
        public string $workoutId,
        public string $movementId,
        public int $restDurationSeconds = 0,
    ) {
    }
}
