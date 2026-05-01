<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\Movement;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;

interface MovementProviderGateway
{
    public function findOneForAdminDetails(string $id): ?MovementDataModel;

    /**
     * @return list<MovementDataModel>
     */
    public function findAllForAdminList(string $sort = 'label', string $direction = 'ASC'): array;

    public function findOneBySlugForUniqueness(string $slug): ?MovementDataModel;

    public function findOneByIdForExerciseAttachment(string $id): ?MovementDataModel;
}
