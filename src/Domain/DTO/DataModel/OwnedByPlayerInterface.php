<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface OwnedByPlayerInterface
{
    public PlayerDataModel $player { get; }
}
