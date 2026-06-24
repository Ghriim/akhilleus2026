<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\User;

use App\Domain\DTO\DataModel\User\FrontThemeDataModel;

interface FrontThemePersisterGateway
{
    public function create(FrontThemeDataModel $model): FrontThemeDataModel;

    public function update(FrontThemeDataModel $model): FrontThemeDataModel;

    public function delete(FrontThemeDataModel $model): void;
}
