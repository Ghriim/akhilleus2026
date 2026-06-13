<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class LogWeightDataInput implements DataInputInterface
{
    public function __construct(
        public \DateTimeImmutable $loggedAt,
        public int $valueGrams,
    ) {
    }
}
