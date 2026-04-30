<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\Equipment;

use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;

interface EquipmentProviderGateway
{
    public function findOneForAdminDetails(string $id): ?EquipmentDataModel;

    /**
     * @return list<EquipmentDataModel>
     */
    public function findAllForAdminList(): array;

    public function findOneBySlugForUniqueness(string $slug): ?EquipmentDataModel;
}
