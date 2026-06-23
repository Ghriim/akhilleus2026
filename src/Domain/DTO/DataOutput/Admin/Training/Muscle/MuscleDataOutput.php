<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Training\Muscle;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class MuscleDataOutput implements DataOutputInterface
{
    public string $id;
    public string $slug;
    public string $label;
}
