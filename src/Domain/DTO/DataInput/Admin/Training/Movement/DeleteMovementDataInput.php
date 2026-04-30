<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Training\Movement;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class DeleteMovementDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
