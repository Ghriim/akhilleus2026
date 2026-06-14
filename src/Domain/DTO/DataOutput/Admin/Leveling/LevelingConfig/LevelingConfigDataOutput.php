<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class LevelingConfigDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public int $xpPerWorkoutMinute,
    ) {
    }
}
