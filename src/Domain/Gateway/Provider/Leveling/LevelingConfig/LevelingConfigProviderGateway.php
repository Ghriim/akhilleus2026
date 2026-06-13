<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Leveling\LevelingConfig;

use App\Domain\DTO\DataModel\Leveling\LevelingConfig\LevelingConfigDataModel;

interface LevelingConfigProviderGateway
{
    /**
     * The single leveling-config row (well-known fixed id). Seeded by migration; this loads it for
     * the admin edit (Phase 3.7) and the workout XP computation (Phase 3.5).
     *
     * @throws \LogicException when the singleton row is missing (mis-seeded environment)
     */
    public function getSingleton(): LevelingConfigDataModel;
}
