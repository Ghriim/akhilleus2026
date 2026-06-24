<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Leveling;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class LockEarnedExperienceDataOutput implements DataOutputInterface
{
    public function __construct(
        public int $entriesLocked,
        public int $playersTouched,
        public int $totalXpAwarded,
        public string $cutoff,
    ) {
    }
}
