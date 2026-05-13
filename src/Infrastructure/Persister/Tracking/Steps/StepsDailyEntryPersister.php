<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Steps;

use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\Gateway\Persister\Tracking\Steps\StepsDailyEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<StepsDailyEntryDataModel>
 */
final readonly class StepsDailyEntryPersister extends AbstractBaseMysqlPersister implements StepsDailyEntryPersisterGateway
{
    public function create(StepsDailyEntryDataModel $model): StepsDailyEntryDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(StepsDailyEntryDataModel $model): StepsDailyEntryDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(StepsDailyEntryDataModel $model): void
    {
        $this->doDelete($model);
    }
}
