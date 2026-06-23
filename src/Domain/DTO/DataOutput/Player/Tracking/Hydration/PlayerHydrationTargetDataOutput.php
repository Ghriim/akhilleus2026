<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Hydration;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class PlayerHydrationTargetDataOutput implements DataOutputInterface
{
    public int $dailyHydrationTargetMl;
}
