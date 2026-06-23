<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\PersonalBest;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DataTransformer\EntityIdDataTransformer;
use App\Domain\DataTransformer\Workout\PersonalBestValueDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class PersonalBestEntryDataOutput implements DataOutputInterface
{
    public string $type;
    #[Map(source: 'value', transform: [PersonalBestValueDataTransformer::class, 'displayableForOutput'])]
    public string $value;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $achievedAt;
    #[Map(source: 'workout', transform: [EntityIdDataTransformer::class, 'idOrNull'])]
    public ?string $workoutId;
    #[Map(source: 'exerciseSet', transform: [EntityIdDataTransformer::class, 'idOrNull'])]
    public ?string $exerciseSetId;
}
