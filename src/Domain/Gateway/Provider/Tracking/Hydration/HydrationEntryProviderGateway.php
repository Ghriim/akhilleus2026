<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface HydrationEntryProviderGateway
{
    /**
     * Player-scoped 404: returns the entry only if its parent summary belongs to `$player`.
     * Used by Update / Delete entry use cases — the gateway filter replaces a manual ownership
     * check at the use-case level.
     */
    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?HydrationEntryDataModel;
}
