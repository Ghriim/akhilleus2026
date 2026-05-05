<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Training\Movement;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class CreateMovementDataInput implements DataInputInterface
{
    /**
     * @param list<string> $secondaryMuscleIds
     * @param list<string> $equipmentIds
     */
    public function __construct(
        public string $label,
        public string $mainMuscleId,
        public array $secondaryMuscleIds,
        public array $equipmentIds,
        public bool $tracksRepetitions,
        public bool $tracksWeight,
        public bool $tracksDuration,
        public bool $tracksDistance,
        public bool $tracksInclinePercent,
        public bool $tracksInclineMeters,
        public ?string $videoLink = null,
        public ?string $gifLink = null,
    ) {
    }
}
