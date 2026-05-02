<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseDetailsDataOutput;

final readonly class WorkoutDetailsDataOutput implements DataOutputInterface
{
    /**
     * @param list<ExerciseDetailsDataOutput> $exercises
     * @param numeric-string|null             $volume
     * @param numeric-string|null             $distance
     * @param numeric-string|null             $inclineMeters
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public ?string $plannedAt,
        public ?string $dateStart,
        public ?string $dateEnd,
        public array $exercises,
        public ?int $duration = null,
        public ?string $volume = null,
        public ?string $distance = null,
        public ?string $inclineMeters = null,
    ) {
    }
}
