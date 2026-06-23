<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Sleep;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class SleepDailyEntryDataOutput implements DataOutputInterface
{
    public string $id;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $date;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $bedAt;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $wakeAt;
    public int $durationMinutes;
    public ?int $quality;
}
