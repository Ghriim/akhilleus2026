<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\User;

use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface PlayerPersisterGateway
{
    public function create(PlayerDataModel $model): PlayerDataModel;

    public function update(PlayerDataModel $model): PlayerDataModel;

    public function delete(PlayerDataModel $model): void;
}
