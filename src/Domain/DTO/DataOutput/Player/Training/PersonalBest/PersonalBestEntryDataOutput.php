<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\PersonalBest;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class PersonalBestEntryDataOutput implements DataOutputInterface
{
    /**
     * @param numeric-string $value
     */
    public function __construct(
        public string $type,
        public string $value,
        public string $achievedAt,
        public ?string $workoutId,
        public ?string $exerciseSetId,
    ) {
    }
}
