<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class WorkoutDataOutput implements DataOutputInterface
{
    public string $id;
    public string $name;
    public string $status;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $plannedAt;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $dateStart;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $dateEnd;
    public ?int $duration;
    /** @var numeric-string|null */
    public ?string $volume;
    /** @var numeric-string|null */
    public ?string $distance;
    /** @var numeric-string|null */
    public ?string $inclineMeters;
}
