<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Movement;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class PlayerMovementListItemDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $label,
        public string $mainMuscleSlug,
        public bool $tracksRepetitions,
        public bool $tracksWeight,
        public bool $tracksDuration,
        public bool $tracksDistance,
        public bool $tracksInclinePercent,
        public bool $tracksInclineMeters,
    ) {
    }
}
