<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationDailySummaryPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<HydrationDailySummaryDataModel>
 */
final readonly class HydrationDailySummaryPersister extends AbstractBaseMysqlPersister implements HydrationDailySummaryPersisterGateway
{
    public function create(HydrationDailySummaryDataModel $model): HydrationDailySummaryDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(HydrationDailySummaryDataModel $model): HydrationDailySummaryDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(HydrationDailySummaryDataModel $model): void
    {
        $this->doDelete($model);
    }
}
