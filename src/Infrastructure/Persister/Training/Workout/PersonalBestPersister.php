<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;
use App\Domain\Gateway\Persister\Training\PersonalBest\PersonalBestPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<PersonalBestDataModel>
 */
final readonly class PersonalBestPersister extends AbstractBaseMysqlPersister implements PersonalBestPersisterGateway
{
    public function create(PersonalBestDataModel $personalBest): PersonalBestDataModel
    {
        $this->doCreate($personalBest);

        return $personalBest;
    }

    public function update(PersonalBestDataModel $personalBest): PersonalBestDataModel
    {
        $this->doUpdate($personalBest);

        return $personalBest;
    }

    public function delete(PersonalBestDataModel $personalBest): void
    {
        $this->doDelete($personalBest);
    }
}
