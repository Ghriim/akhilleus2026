<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class LevelBracketDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public int $fromLevel,
        public ?int $toLevel,
        public int $coefficientA,
        public int $exponentK,
        public int $offsetB,
    ) {
    }
}
