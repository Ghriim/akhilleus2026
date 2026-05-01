<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateExerciseSetAchievedDataInput implements DataInputInterface
{
    /**
     * @param numeric-string|null $achievedWeight
     * @param numeric-string|null $achievedDistanceMeters
     * @param numeric-string|null $achievedInclinePercent
     * @param numeric-string|null $achievedInclineMeters
     */
    public function __construct(
        public string $exerciseSetId,
        public ?int $achievedReps = null,
        public ?string $achievedWeight = null,
        public ?int $achievedDurationSeconds = null,
        public ?string $achievedDistanceMeters = null,
        public ?string $achievedInclinePercent = null,
        public ?string $achievedInclineMeters = null,
    ) {
    }
}
