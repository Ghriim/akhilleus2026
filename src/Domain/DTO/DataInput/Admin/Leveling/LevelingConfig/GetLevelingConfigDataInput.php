<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\DataInputInterface;

/**
 * The leveling config is a singleton (well-known fixed id), so reading it carries no parameters.
 */
final readonly class GetLevelingConfigDataInput implements DataInputInterface
{
}
