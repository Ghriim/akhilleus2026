<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpsertStepsForDayDataInput implements DataInputInterface
{
    public function __construct(
        public \DateTimeImmutable $date,
        public int $count,
    ) {
    }
}
