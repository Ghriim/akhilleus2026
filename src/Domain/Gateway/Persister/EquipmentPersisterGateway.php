<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\Equipment\EquipmentDataModel;

interface EquipmentPersisterGateway
{
    public function create(EquipmentDataModel $model): EquipmentDataModel;

    public function update(EquipmentDataModel $model): EquipmentDataModel;

    public function delete(EquipmentDataModel $model): void;
}
