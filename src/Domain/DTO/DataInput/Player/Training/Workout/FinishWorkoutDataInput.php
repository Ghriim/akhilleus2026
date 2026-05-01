<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class FinishWorkoutDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
