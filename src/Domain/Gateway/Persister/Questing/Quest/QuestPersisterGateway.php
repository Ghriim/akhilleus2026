<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Questing\Quest;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;

interface QuestPersisterGateway
{
    public function create(QuestDataModel $model): QuestDataModel;

    public function update(QuestDataModel $model): QuestDataModel;

    public function delete(QuestDataModel $model): void;
}
