<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Questing\Quest;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<QuestDataModel>
 */
final readonly class QuestPersister extends AbstractBaseMysqlPersister implements QuestPersisterGateway
{
    public function create(QuestDataModel $model): QuestDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(QuestDataModel $model): QuestDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(QuestDataModel $model): void
    {
        $this->doDelete($model);
    }
}
