<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Questing\Quest;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class QuestListItemDataOutput implements DataOutputInterface
{
    public string $id;
    public string $label;
    public string $kind;
    public ?string $metric;
    public string $periodicity;
    public ?string $targetValue;
    public int $rewardedXp;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $dateStart;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $dateEnd;
}
