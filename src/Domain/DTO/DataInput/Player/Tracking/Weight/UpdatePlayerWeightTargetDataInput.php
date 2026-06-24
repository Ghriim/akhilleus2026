<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdatePlayerWeightTargetDataInput implements DataInputInterface
{
    public function __construct(
        public int $targetGrams,
    ) {
    }
}
