<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\Muscle;

use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;

interface MuscleProviderGateway
{
    public function findOneForAdminDetails(string $id): ?MuscleDataModel;

    /**
     * @return list<MuscleDataModel>
     */
    public function findAllForAdminList(): array;

    public function findOneBySlugForUniqueness(string $slug): ?MuscleDataModel;
}
