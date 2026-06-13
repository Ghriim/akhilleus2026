<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Leveling\LevelBracket;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\Gateway\Persister\Leveling\LevelBracket\LevelBracketPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<LevelBracketDataModel>
 */
final readonly class LevelBracketPersister extends AbstractBaseMysqlPersister implements LevelBracketPersisterGateway
{
    public function create(LevelBracketDataModel $model): LevelBracketDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(LevelBracketDataModel $model): LevelBracketDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(LevelBracketDataModel $model): void
    {
        $this->doDelete($model);
    }
}
