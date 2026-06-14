<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Leveling\EarnedExperience;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class EarnedExperienceDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $id,
        public string $label,
        public int $amount,
        public ?string $earnedAt,
        public string $sourceType,
        public string $sourceId,
        public bool $isLocked,
    ) {
    }
}
