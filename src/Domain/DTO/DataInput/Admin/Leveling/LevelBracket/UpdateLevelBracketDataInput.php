<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateLevelBracketDataInput implements DataInputInterface
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
