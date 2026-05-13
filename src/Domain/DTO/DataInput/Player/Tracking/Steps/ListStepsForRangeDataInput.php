<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ListStepsForRangeDataInput implements DataInputInterface
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
