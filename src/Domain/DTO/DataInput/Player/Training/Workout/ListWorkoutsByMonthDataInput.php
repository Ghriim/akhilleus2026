<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ListWorkoutsByMonthDataInput implements DataInputInterface
{
    public const int MIN_YEAR = 2000;
    public const int MAX_YEAR = 2100;

    public function __construct(
        public int $year,
        public int $month,
    ) {
    }
}
