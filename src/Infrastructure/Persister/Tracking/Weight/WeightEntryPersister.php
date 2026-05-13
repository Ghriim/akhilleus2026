<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Weight;

use App\Domain\DTO\DataModel\Tracking\Weight\WeightEntryDataModel;
use App\Domain\Gateway\Persister\Tracking\Weight\WeightEntryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * Re-derives the `date` column from `loggedAt` on every persist so the (player, date) unique
 * constraint stays in sync if `loggedAt` is mutated (the constructor does the same, but on
 * update the caller may have changed `loggedAt` without touching `date`).
 *
 * @extends AbstractBaseMysqlPersister<WeightEntryDataModel>
 */
final readonly class WeightEntryPersister extends AbstractBaseMysqlPersister implements WeightEntryPersisterGateway
{
    public function create(WeightEntryDataModel $model): WeightEntryDataModel
    {
        $model->date = $model->loggedAt->setTime(0, 0, 0);
        $this->doCreate($model);

        return $model;
    }

    public function update(WeightEntryDataModel $model): WeightEntryDataModel
    {
        $model->date = $model->loggedAt->setTime(0, 0, 0);
        $this->doUpdate($model);

        return $model;
    }

    public function delete(WeightEntryDataModel $model): void
    {
        $this->doDelete($model);
    }
}
