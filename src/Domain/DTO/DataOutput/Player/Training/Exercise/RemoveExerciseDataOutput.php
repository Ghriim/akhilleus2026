<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Exercise;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class RemoveExerciseDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $deletedId,
    ) {
    }
}
