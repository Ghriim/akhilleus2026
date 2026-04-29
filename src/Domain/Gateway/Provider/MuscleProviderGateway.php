<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\Muscle\MuscleDataModel;

interface MuscleProviderGateway
{
    public function findOneForAdminDetails(string $id): ?MuscleDataModel;

    /**
     * @return list<MuscleDataModel>
     */
    public function findAllForAdminList(): array;

    public function findOneBySlugForUniqueness(string $slug): ?MuscleDataModel;
}
