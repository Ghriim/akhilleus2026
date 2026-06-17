<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\Workout;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class DeleteWorkoutDataOutput implements DataOutputInterface
{
    /**
     * @param 'hard'|'soft' $mode `hard` = the workout (and its children) were physically removed
     *                            (same-day delete); `soft` = the workout was kept but transitioned to
     *                            the DELETED status, preserving any locked XP (past/other-day delete)
     */
    public function __construct(
        public string $deletedId,
        public string $mode,
    ) {
    }
}
