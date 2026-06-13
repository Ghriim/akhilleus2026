<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Hydration;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class HydrationEntryDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $loggedAt,
        public int $valueMl,
    ) {
    }
}
