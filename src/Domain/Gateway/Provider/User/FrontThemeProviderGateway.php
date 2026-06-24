<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\User;

use App\Domain\DTO\DataModel\User\FrontThemeDataModel;

interface FrontThemeProviderGateway
{
    public function findOneByIdForAdminAction(string $id): ?FrontThemeDataModel;

    public function findOneByNameForUniqueness(string $name): ?FrontThemeDataModel;

    /**
     * @return list<FrontThemeDataModel>
     */
    public function findAllForAdminList(): array;
}
