<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Leveling\LevelBracket;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;

interface LevelBracketPersisterGateway
{
    public function create(LevelBracketDataModel $model): LevelBracketDataModel;

    public function update(LevelBracketDataModel $model): LevelBracketDataModel;

    public function delete(LevelBracketDataModel $model): void;
}
