<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class WorkoutDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $status,
        public ?\DateTimeImmutable $plannedAt,
        public ?\DateTimeImmutable $dateStart,
        public ?\DateTimeImmutable $dateEnd,
    ) {
    }
}
