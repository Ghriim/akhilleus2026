<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\ExerciseSet;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class RemoveExerciseSetDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $deletedId,
    ) {
    }
}
