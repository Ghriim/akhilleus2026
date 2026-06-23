<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Movement;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class PlayerMovementListItemDataOutput implements DataOutputInterface
{
    public string $id;
    public string $slug;
    public string $label;
    #[Map(source: 'mainMuscle.slug')]
    public string $mainMuscleSlug;
    public bool $tracksRepetitions;
    public bool $tracksWeight;
    public bool $tracksDuration;
    public bool $tracksDistance;
    public bool $tracksInclinePercent;
    public bool $tracksInclineMeters;
}
