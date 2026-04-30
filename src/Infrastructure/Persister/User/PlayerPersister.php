<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

final readonly class PlayerPersister extends AbstractBaseMysqlPersister implements PlayerPersisterGateway
{
    public function create(PlayerDataModel $model): PlayerDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(PlayerDataModel $model): PlayerDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(PlayerDataModel $model): void
    {
        $this->doDelete($model);
    }
}
