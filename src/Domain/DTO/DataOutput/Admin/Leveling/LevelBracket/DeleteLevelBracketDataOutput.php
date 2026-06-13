<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class DeleteLevelBracketDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $deletedId,
    ) {
    }
}
