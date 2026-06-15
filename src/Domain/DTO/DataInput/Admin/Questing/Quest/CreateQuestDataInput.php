<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class CreateQuestDataInput implements DataInputInterface
{
    public function __construct(
        public string $label,
        public string $kind,
        public string $periodicity,
        public int $rewardedXp,
        public ?string $metric = null,
        public ?string $targetValue = null,
        public ?\DateTimeImmutable $dateStart = null,
        public ?\DateTimeImmutable $dateEnd = null,
    ) {
    }
}
