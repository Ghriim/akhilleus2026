<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class CreateMuscleDataInput implements DataInputInterface
{
    public function __construct(
        public string $label,
    ) {
    }
}
