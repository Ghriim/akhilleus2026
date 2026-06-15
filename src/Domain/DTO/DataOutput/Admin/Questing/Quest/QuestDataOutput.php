<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Questing\Quest;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class QuestDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public string $kind,
        public ?string $metric,
        public string $periodicity,
        public ?string $targetValue,
        public int $rewardedXp,
        public ?string $dateStart,
        public ?string $dateEnd,
    ) {
    }
}
