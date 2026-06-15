<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Questing;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ClaimQuestRewardDataInput implements DataInputInterface
{
    public function __construct(
        public string $progressionId,
    ) {
    }
}
