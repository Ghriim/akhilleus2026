<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\Muscle\MuscleDataModel;

interface MusclePersisterGateway
{
    public function create(MuscleDataModel $model): MuscleDataModel;

    public function update(MuscleDataModel $model): MuscleDataModel;

    public function delete(MuscleDataModel $model): void;
}
