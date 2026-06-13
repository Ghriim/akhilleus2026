<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ListWeightForRangeDataInput implements DataInputInterface
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
