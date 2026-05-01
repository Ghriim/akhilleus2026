<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class PersonalBestSummaryDataOutput implements DataOutputInterface
{
    /**
     * @param numeric-string $value
     */
    public function __construct(
        public string $movementId,
        public string $movementSlug,
        public string $movementLabel,
        public string $type,
        public string $value,
        public string $achievedAt,
        public ?string $exerciseSetId,
    ) {
    }
}
