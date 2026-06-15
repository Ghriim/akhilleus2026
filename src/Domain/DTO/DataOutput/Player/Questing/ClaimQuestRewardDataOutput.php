<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Questing;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class ClaimQuestRewardDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $progressionId,
        public string $earnedExperienceId,
        public int $amount,
    ) {
    }
}
