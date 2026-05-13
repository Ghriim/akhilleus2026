<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Steps;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class StepsDailyEntryDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $date,
        public int $count,
    ) {
    }
}
