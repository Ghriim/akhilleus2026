<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Tracking\Weight;

use App\Domain\DTO\DataModel\Tracking\Weight\WeightEntryDataModel;

interface WeightEntryPersisterGateway
{
    public function create(WeightEntryDataModel $model): WeightEntryDataModel;

    public function update(WeightEntryDataModel $model): WeightEntryDataModel;

    public function delete(WeightEntryDataModel $model): void;
}
