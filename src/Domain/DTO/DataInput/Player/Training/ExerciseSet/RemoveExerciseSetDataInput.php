<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class RemoveExerciseSetDataInput implements DataInputInterface
{
    public function __construct(
        public string $exerciseSetId,
    ) {
    }
}
