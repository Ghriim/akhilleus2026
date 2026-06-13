<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Weight;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class WeightEntryDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $date,
        public string $loggedAt,
        public int $valueGrams,
    ) {
    }
}
