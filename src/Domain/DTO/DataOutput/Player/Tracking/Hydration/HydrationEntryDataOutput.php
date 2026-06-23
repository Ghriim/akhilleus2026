<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Hydration;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class HydrationEntryDataOutput implements DataOutputInterface
{
    public string $id;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $loggedAt;
    public int $valueMl;
}
