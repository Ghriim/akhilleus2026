<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Sleep;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class PlayerSleepTargetDataOutput implements DataOutputInterface
{
    public int $dailySleepTargetMinutes;
}
