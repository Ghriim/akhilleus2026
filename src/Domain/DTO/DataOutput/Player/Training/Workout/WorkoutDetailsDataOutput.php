<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseDetailsDataOutput;

final readonly class WorkoutDetailsDataOutput implements DataOutputInterface
{
    /**
     * @param list<ExerciseDetailsDataOutput> $exercises
     */
    public function __construct(
        public string $id,
        public string $status,
        public ?string $plannedAt,
        public ?string $dateStart,
        public ?string $dateEnd,
        public array $exercises,
    ) {
    }
}
