<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Exercise;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use App\Domain\DTO\DataOutput\Player\Training\ExerciseSet\ExerciseSetDataOutput;

final readonly class ExerciseDetailsDataOutput implements DataOutputInterface
{
    /**
     * @param list<ExerciseSetDataOutput> $sets
     */
    public function __construct(
        public string $id,
        public int $position,
        public int $restDurationSeconds,
        public ExerciseMovementDataOutput $movement,
        public array $sets,
    ) {
    }
}
