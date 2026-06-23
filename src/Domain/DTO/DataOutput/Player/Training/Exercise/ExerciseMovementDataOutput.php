<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Exercise;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class ExerciseMovementDataOutput implements DataOutputInterface
{
    public string $id;
    public string $slug;
    public string $label;
    public bool $tracksRepetitions;
    public bool $tracksWeight;
    public bool $tracksDuration;
    public bool $tracksDistance;
    public bool $tracksInclinePercent;
    public bool $tracksInclineMeters;
    public ?string $videoLink;
    public ?string $gifLink;
}
