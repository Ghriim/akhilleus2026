<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\Movement\MovementDataModel;

interface MovementProviderGateway
{
    public function findOneForAdminDetails(string $id): ?MovementDataModel;

    /**
     * @return list<MovementDataModel>
     */
    public function findAllForAdminList(): array;

    public function findOneBySlugForUniqueness(string $slug): ?MovementDataModel;
}
