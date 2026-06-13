<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ListSleepForRangeDataInput implements DataInputInterface
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
