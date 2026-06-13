<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateHydrationEntryDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
        public int $valueMl,
    ) {
    }
}
