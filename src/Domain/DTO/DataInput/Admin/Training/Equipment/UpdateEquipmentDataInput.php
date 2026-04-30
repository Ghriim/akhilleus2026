<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateEquipmentDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
        public string $label,
    ) {
    }
}
