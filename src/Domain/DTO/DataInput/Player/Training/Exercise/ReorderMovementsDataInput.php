<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Training\Exercise;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ReorderMovementsDataInput implements DataInputInterface
{
    /**
     * @param list<string> $orderedExerciseIds
     */
    public function __construct(
        public string $workoutId,
        public array $orderedExerciseIds,
    ) {
    }
}
