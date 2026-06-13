<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Sleep;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class DeleteSleepDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $deletedId,
    ) {
    }
}
