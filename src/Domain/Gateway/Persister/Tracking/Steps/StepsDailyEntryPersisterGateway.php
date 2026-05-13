<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Tracking\Steps;

use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;

interface StepsDailyEntryPersisterGateway
{
    public function create(StepsDailyEntryDataModel $model): StepsDailyEntryDataModel;

    public function update(StepsDailyEntryDataModel $model): StepsDailyEntryDataModel;

    public function delete(StepsDailyEntryDataModel $model): void;
}
