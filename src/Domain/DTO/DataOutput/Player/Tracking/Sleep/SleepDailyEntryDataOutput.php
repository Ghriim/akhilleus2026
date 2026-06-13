<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Sleep;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class SleepDailyEntryDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $date,
        public string $bedAt,
        public string $wakeAt,
        public int $durationMinutes,
        public ?int $quality,
    ) {
    }
}
