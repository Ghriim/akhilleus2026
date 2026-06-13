<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class DeleteWeightDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
