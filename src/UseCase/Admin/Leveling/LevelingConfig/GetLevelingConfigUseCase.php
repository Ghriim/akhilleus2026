<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\GetLevelingConfigDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelingConfig\LevelingConfigDataOutput;
use App\Domain\Gateway\Provider\Leveling\LevelingConfig\LevelingConfigProviderGateway;
use App\UseCase\AbstractPublicUseCase;

final class GetLevelingConfigUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly LevelingConfigProviderGateway $levelingConfigProvider,
    ) {
    }

    /**
     * @param GetLevelingConfigDataInput $input
     */
    public function execute(DataInputInterface $input): LevelingConfigDataOutput
    {
        $config = $this->levelingConfigProvider->getSingleton();

        return new LevelingConfigDataOutput($config->id, $config->xpPerWorkoutMinute);
    }
}
