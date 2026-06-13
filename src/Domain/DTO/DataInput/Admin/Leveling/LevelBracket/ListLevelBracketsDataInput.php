<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\DataInputInterface;

/**
 * The bracket list is inherently ordered by `fromLevel` (it models a contiguous curve), so this
 * input carries no sort/direction options — unlike the Equipment/Muscle admin lists.
 */
final readonly class ListLevelBracketsDataInput implements DataInputInterface
{
}
