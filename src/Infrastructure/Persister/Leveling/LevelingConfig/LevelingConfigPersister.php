<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Leveling\LevelingConfig;

use App\Domain\DTO\DataModel\Leveling\LevelingConfig\LevelingConfigDataModel;
use App\Domain\Gateway\Persister\Leveling\LevelingConfig\LevelingConfigPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<LevelingConfigDataModel>
 */
final readonly class LevelingConfigPersister extends AbstractBaseMysqlPersister implements LevelingConfigPersisterGateway
{
    public function update(LevelingConfigDataModel $model): LevelingConfigDataModel
    {
        $this->doUpdate($model);

        return $model;
    }
}
