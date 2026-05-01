<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class FinishWorkoutDataOutput implements DataOutputInterface
{
    /**
     * @param list<PersonalBestSummaryDataOutput> $newPersonalBests
     */
    public function __construct(
        public WorkoutDataOutput $workout,
        public array $newPersonalBests,
    ) {
    }
}
