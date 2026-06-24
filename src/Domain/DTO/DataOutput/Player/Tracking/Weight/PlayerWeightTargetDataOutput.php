<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Tracking\Weight;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class PlayerWeightTargetDataOutput implements DataOutputInterface
{
    public ?int $targetWeightGrams;
}
