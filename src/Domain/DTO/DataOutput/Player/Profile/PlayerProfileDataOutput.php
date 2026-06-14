<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Profile;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class PlayerProfileDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $displayName,
        public int $level,
        public int $currentXp,
        public int $xpToNextLevel,
    ) {
    }
}
