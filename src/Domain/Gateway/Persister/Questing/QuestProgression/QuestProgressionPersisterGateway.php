<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Questing\QuestProgression;

use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;

interface QuestProgressionPersisterGateway
{
    public function create(QuestProgressionDataModel $model): QuestProgressionDataModel;

    public function update(QuestProgressionDataModel $model): QuestProgressionDataModel;

    public function delete(QuestProgressionDataModel $model): void;
}
