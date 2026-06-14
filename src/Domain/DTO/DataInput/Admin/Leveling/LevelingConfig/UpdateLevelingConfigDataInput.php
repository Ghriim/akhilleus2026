<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateLevelingConfigDataInput implements DataInputInterface
{
    public function __construct(
        public int $xpPerWorkoutMinute,
    ) {
    }
}
