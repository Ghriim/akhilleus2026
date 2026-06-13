<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateSleepDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $bedAt,
        public \DateTimeImmutable $wakeAt,
        public ?int $quality = null,
    ) {
    }
}
