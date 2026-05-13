<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;

interface SleepDailyEntryPersisterGateway
{
    public function create(SleepDailyEntryDataModel $model): SleepDailyEntryDataModel;

    public function update(SleepDailyEntryDataModel $model): SleepDailyEntryDataModel;

    public function delete(SleepDailyEntryDataModel $model): void;
}
