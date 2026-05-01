<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training\PersonalBest;

use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;

interface PersonalBestPersisterGateway
{
    public function create(PersonalBestDataModel $personalBest): PersonalBestDataModel;

    public function update(PersonalBestDataModel $personalBest): PersonalBestDataModel;

    public function delete(PersonalBestDataModel $personalBest): void;
}
