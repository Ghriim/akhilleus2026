<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\Gateway\Persister\Tracking\Sleep\SleepDailyEntryPersisterGateway;
use App\Domain\Service\Tracking\Sleep\SleepDurationEvaluator;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<SleepDailyEntryDataModel>
 */
final readonly class SleepDailyEntryPersister extends AbstractBaseMysqlPersister implements SleepDailyEntryPersisterGateway
{
    public function create(SleepDailyEntryDataModel $model): SleepDailyEntryDataModel
    {
        SleepDurationEvaluator::recompute($model);
        $this->doCreate($model);

        return $model;
    }

    public function update(SleepDailyEntryDataModel $model): SleepDailyEntryDataModel
    {
        SleepDurationEvaluator::recompute($model);
        $this->doUpdate($model);

        return $model;
    }

    public function delete(SleepDailyEntryDataModel $model): void
    {
        $this->doDelete($model);
    }
}
