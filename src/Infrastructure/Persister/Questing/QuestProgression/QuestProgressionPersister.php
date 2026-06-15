<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Questing\QuestProgression;

use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<QuestProgressionDataModel>
 */
final readonly class QuestProgressionPersister extends AbstractBaseMysqlPersister implements QuestProgressionPersisterGateway
{
    public function create(QuestProgressionDataModel $model): QuestProgressionDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(QuestProgressionDataModel $model): QuestProgressionDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(QuestProgressionDataModel $model): void
    {
        $this->doDelete($model);
    }
}
