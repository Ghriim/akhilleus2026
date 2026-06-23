<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Questing;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class QuestProgressionDataOutput implements DataOutputInterface
{
    public string $id;
    #[Map(source: 'quest.id')]
    public string $questId;
    #[Map(source: 'quest.label')]
    public string $label;
    #[Map(source: 'quest.kind')]
    public string $kind;
    #[Map(source: 'quest.metric')]
    public ?string $metric;
    #[Map(source: 'quest.periodicity')]
    public string $periodicity;
    public ?string $currentValue;
    #[Map(source: 'quest.targetValue')]
    public ?string $targetValue;
    #[Map(source: 'quest.rewardedXp')]
    public int $rewardedXp;
    public string $status;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $startDate;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $endDate;
}
