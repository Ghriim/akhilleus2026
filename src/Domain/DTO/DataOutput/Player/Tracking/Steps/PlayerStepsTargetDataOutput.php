<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Steps;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class PlayerStepsTargetDataOutput implements DataOutputInterface
{
    public function __construct(
        public int $dailyStepsTarget,
    ) {
    }
}
