<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Profile;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class PlayerProfileDataOutput implements DataOutputInterface
{
    public string $id;
    public string $displayName;
    public int $level;
    public int $currentXp;
    public int $xpToNextLevel;
    public int $dailySleepTargetMinutes;
    public ?int $targetWeightGrams;
}
