<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;

interface HydrationEntryPersisterGateway
{
    public function create(HydrationEntryDataModel $model): HydrationEntryDataModel;

    public function update(HydrationEntryDataModel $model): HydrationEntryDataModel;

    public function delete(HydrationEntryDataModel $model): void;
}
