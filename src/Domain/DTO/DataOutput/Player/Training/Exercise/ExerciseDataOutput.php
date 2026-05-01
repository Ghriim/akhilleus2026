<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Exercise;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class ExerciseDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $workoutId,
        public int $position,
        public int $restDurationSeconds,
        public ExerciseMovementDataOutput $movement,
    ) {
    }
}
