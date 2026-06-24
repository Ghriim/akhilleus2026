<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User\FrontTheme;

use App\Domain\DTO\DataModel\User\FrontThemeDataModel;
use App\Domain\Gateway\Persister\User\FrontThemePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<FrontThemeDataModel>
 */
final readonly class FrontThemePersister extends AbstractBaseMysqlPersister implements FrontThemePersisterGateway
{
    public function create(FrontThemeDataModel $model): FrontThemeDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(FrontThemeDataModel $model): FrontThemeDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(FrontThemeDataModel $model): void
    {
        $this->doDelete($model);
    }
}
