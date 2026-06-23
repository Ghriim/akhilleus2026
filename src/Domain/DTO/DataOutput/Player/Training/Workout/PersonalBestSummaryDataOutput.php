<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DataTransformer\EntityIdDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class PersonalBestSummaryDataOutput implements DataOutputInterface
{
    #[Map(source: 'movement.id')]
    public string $movementId;
    #[Map(source: 'movement.slug')]
    public string $movementSlug;
    #[Map(source: 'movement.label')]
    public string $movementLabel;
    public string $type;
    /** @var numeric-string */
    public string $value;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $achievedAt;
    #[Map(source: 'exerciseSet', transform: [EntityIdDataTransformer::class, 'idOrNull'])]
    public ?string $exerciseSetId;
}
