<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Leveling\EarnedExperience;

use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<EarnedExperienceDataModel>
 */
final readonly class EarnedExperiencePersister extends AbstractBaseMysqlPersister implements EarnedExperiencePersisterGateway
{
    public function create(EarnedExperienceDataModel $model): EarnedExperienceDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(EarnedExperienceDataModel $model): EarnedExperienceDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(EarnedExperienceDataModel $model): void
    {
        $this->doDelete($model);
    }
}
