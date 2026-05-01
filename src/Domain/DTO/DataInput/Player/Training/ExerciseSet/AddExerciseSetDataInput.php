<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class AddExerciseSetDataInput implements DataInputInterface
{
    /**
     * @param numeric-string|null $plannedWeight
     * @param numeric-string|null $plannedDistanceMeters
     * @param numeric-string|null $plannedInclinePercent
     * @param numeric-string|null $plannedInclineMeters
     */
    public function __construct(
        public string $exerciseId,
        public ?int $plannedReps = null,
        public ?string $plannedWeight = null,
        public ?int $plannedDurationSeconds = null,
        public ?string $plannedDistanceMeters = null,
        public ?string $plannedInclinePercent = null,
        public ?string $plannedInclineMeters = null,
    ) {
    }
}
