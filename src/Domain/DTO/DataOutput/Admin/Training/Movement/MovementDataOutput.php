<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Training\Movement;

use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentListItemDataOutput;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleListItemDataOutput;
use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class MovementDataOutput implements DataOutputInterface
{
    /**
     * @param list<MuscleListItemDataOutput>    $secondaryMuscles
     * @param list<EquipmentListItemDataOutput> $equipments
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $label,
        public MuscleListItemDataOutput $mainMuscle,
        public array $secondaryMuscles,
        public array $equipments,
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
