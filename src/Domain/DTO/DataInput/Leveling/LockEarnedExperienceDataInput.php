<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Leveling;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class LockEarnedExperienceDataInput implements DataInputInterface
{
    /**
     * @param ?\DateTimeImmutable $cutoff Day boundary; entries earned strictly before it are locked.
     *                                    Null lets the use case default to today 00:00 Europe/Paris.
     */
    public function __construct(
        public ?\DateTimeImmutable $cutoff = null,
    ) {
    }
}
