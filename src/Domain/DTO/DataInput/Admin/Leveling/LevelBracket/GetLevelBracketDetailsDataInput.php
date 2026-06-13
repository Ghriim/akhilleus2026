<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class GetLevelBracketDetailsDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
