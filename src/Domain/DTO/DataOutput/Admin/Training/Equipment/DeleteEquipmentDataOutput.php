<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Training\Equipment;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class DeleteEquipmentDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $deletedId,
    ) {
    }
}
