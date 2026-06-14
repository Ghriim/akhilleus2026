<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Leveling\EarnedExperience;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ListEarnedExperienceDataInput implements DataInputInterface
{
    public const int DEFAULT_PER_PAGE = 20;
    public const int MAX_PER_PAGE = 50;

    public function __construct(
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
    }
}
