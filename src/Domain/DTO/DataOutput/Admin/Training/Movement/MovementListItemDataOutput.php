<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Training\Movement;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class MovementListItemDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $label,
        public string $mainMuscleSlug,
    ) {
    }
}
