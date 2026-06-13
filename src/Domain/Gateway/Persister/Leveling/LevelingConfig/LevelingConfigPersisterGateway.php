<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Leveling\LevelingConfig;

use App\Domain\DTO\DataModel\Leveling\LevelingConfig\LevelingConfigDataModel;

/**
 * Only `update` is exposed: the singleton is seeded by migration (its fixed id would be clobbered
 * by the base persister's ULID generation on create) and is never deleted. Admin edits (Phase 3.7)
 * go through `update`.
 */
interface LevelingConfigPersisterGateway
{
    public function update(LevelingConfigDataModel $model): LevelingConfigDataModel;
}
