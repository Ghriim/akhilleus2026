<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Steps;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class StepsDailyEntryDataOutput implements DataOutputInterface
{
    public string $id;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $date;
    public int $count;
    public int $target;
}
