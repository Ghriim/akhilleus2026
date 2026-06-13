<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Hydration;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class HydrationDayDataOutput implements DataOutputInterface
{
    /**
     * @param list<HydrationEntryDataOutput> $entries
     */
    public function __construct(
        public string $date,
        public int $targetMl,
        public int $amountConsumedMl,
        public array $entries,
    ) {
    }
}
