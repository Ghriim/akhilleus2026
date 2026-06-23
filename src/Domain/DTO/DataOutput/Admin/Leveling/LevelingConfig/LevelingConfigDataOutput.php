<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class LevelingConfigDataOutput implements DataOutputInterface
{
    public string $id;
    public int $xpPerWorkoutMinute;
}
