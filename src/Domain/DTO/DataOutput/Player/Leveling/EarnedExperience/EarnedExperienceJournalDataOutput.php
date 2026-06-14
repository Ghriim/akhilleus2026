<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Leveling\EarnedExperience;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class EarnedExperienceJournalDataOutput implements DataOutputInterface
{
    /**
     * @param list<EarnedExperienceDataOutput> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $perPage,
        public int $totalCount,
    ) {
    }
}
