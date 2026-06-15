<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateQuestDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public string $kind,
        public string $periodicity,
        public int $rewardedXp,
        public ?string $metric,
        public ?string $targetValue,
        public \DateTimeImmutable $dateStart,
        public ?\DateTimeImmutable $dateEnd,
    ) {
    }
}
