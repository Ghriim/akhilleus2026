<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateMuscleDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
        public string $label,
    ) {
    }
}
