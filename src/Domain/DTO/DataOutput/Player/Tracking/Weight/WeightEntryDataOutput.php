<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Weight;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class WeightEntryDataOutput implements DataOutputInterface
{
    public string $id;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $date;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $loggedAt;
    public int $valueGrams;
}
