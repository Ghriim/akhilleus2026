<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;

final readonly class PersonalBestUpsert
{
    public function __construct(
        public PersonalBestDataModel $personalBest,
        public bool $isNew,
    ) {
    }
}
