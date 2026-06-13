<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Leveling\EarnedExperience;

use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;

interface EarnedExperiencePersisterGateway
{
    public function create(EarnedExperienceDataModel $model): EarnedExperienceDataModel;

    public function update(EarnedExperienceDataModel $model): EarnedExperienceDataModel;

    public function delete(EarnedExperienceDataModel $model): void;
}
