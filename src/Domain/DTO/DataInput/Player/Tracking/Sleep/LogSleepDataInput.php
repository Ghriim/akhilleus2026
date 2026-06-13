<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class LogSleepDataInput implements DataInputInterface
{
    public function __construct(
        public \DateTimeImmutable $bedAt,
        public \DateTimeImmutable $wakeAt,
        public ?int $quality = null,
    ) {
    }
}
