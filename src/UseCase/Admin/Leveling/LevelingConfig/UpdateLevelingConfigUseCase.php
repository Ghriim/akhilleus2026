<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\UpdateLevelingConfigDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Leveling\LevelingConfig\LevelingConfigDataOutput;
use App\Domain\Gateway\Persister\Leveling\LevelingConfig\LevelingConfigPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\LevelingConfig\LevelingConfigProviderGateway;
use App\Domain\Validator\Admin\Leveling\LevelingConfig\UpdateLevelingConfigValidator;
use App\UseCase\AbstractLoggedAdminUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateLevelingConfigUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly UpdateLevelingConfigValidator $updateLevelingConfigValidator,
        private readonly LevelingConfigProviderGateway $levelingConfigProvider,
        private readonly LevelingConfigPersisterGateway $levelingConfigPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateLevelingConfigDataInput $input
     */
    public function execute(DataInputInterface $input): LevelingConfigDataOutput
    {
        $this->updateLevelingConfigValidator->validate($input);

        $config = $this->levelingConfigProvider->getSingleton();
        $config->xpPerWorkoutMinute = $input->xpPerWorkoutMinute;
        $this->levelingConfigPersister->update($config);

        return $this->mapper->map($config, LevelingConfigDataOutput::class);
    }
}
