<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\ExerciseSet;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class ExerciseSetDataOutput implements DataOutputInterface
{
    public string $id;
    #[Map(source: 'exercise.id')]
    public string $exerciseId;
    public int $position;
    public ?int $plannedReps;
    public ?int $achievedReps;
    public ?string $plannedWeight;
    public ?string $achievedWeight;
    public ?int $plannedDurationSeconds;
    public ?int $achievedDurationSeconds;
    public ?string $plannedDistanceMeters;
    public ?string $achievedDistanceMeters;
    public ?string $plannedInclinePercent;
    public ?string $achievedInclinePercent;
    public ?string $plannedInclineMeters;
    public ?string $achievedInclineMeters;
    public bool $isComplete;
}
