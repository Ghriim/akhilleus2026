<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\Movement\MovementDataModel;

interface MovementPersisterGateway
{
    public function create(MovementDataModel $model): MovementDataModel;

    public function update(MovementDataModel $model): MovementDataModel;

    public function delete(MovementDataModel $model): void;
}
