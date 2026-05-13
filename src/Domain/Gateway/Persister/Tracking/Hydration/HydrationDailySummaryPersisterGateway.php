<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;

interface HydrationDailySummaryPersisterGateway
{
    public function create(HydrationDailySummaryDataModel $model): HydrationDailySummaryDataModel;

    public function update(HydrationDailySummaryDataModel $model): HydrationDailySummaryDataModel;

    public function delete(HydrationDailySummaryDataModel $model): void;
}
