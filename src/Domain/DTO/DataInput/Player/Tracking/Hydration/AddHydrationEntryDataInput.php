<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class AddHydrationEntryDataInput implements DataInputInterface
{
    public function __construct(
        public \DateTimeImmutable $loggedAt,
        public int $valueMl,
    ) {
    }
}
