<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Questing;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class QuestProgressionDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $questId,
        public string $label,
        public string $kind,
        public ?string $metric,
        public string $periodicity,
        public ?string $currentValue,
        public ?string $targetValue,
        public int $rewardedXp,
        public string $status,
        public ?string $startDate,
        public ?string $endDate,
    ) {
    }
}
