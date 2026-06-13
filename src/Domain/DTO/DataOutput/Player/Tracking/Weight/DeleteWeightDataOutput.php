<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Weight;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class DeleteWeightDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $deletedId,
    ) {
    }
}
