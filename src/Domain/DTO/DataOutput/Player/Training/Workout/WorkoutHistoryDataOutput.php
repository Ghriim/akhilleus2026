<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class WorkoutHistoryDataOutput implements DataOutputInterface
{
    /**
     * @param list<WorkoutDataOutput> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $perPage,
        public int $totalCount,
    ) {
    }
}
