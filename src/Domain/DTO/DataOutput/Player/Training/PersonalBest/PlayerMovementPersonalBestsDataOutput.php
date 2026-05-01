<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\PersonalBest;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class PlayerMovementPersonalBestsDataOutput implements DataOutputInterface
{
    /**
     * @param list<PersonalBestEntryDataOutput> $personalBests
     */
    public function __construct(
        public MovementSummaryDataOutput $movement,
        public array $personalBests,
    ) {
    }
}
