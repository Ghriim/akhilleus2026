<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\UpdateLevelingConfigDataInput;
use App\Domain\DTO\DataModel\Leveling\LevelingConfig\LevelingConfigDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Leveling\LevelingConfig\LevelingConfigProviderGateway;
use App\Domain\Validator\Admin\Leveling\LevelingConfig\UpdateLevelingConfigValidator;
use App\UseCase\Admin\Leveling\LevelingConfig\UpdateLevelingConfigUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateLevelingConfigUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTheSingletonAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $output = $container->get(UpdateLevelingConfigUseCase::class)->execute(
            new UpdateLevelingConfigDataInput(75),
        );

        self::assertSame(LevelingConfigDataModel::LEVELING_CONFIG_ID, $output->id);
        self::assertSame(75, $output->xpPerWorkoutMinute);
        self::assertSame(75, $container->get(LevelingConfigProviderGateway::class)->getSingleton()->xpPerWorkoutMinute);
    }

    public function testItRejectsAValueBelowTheMinimum(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        try {
            $container->get(UpdateLevelingConfigUseCase::class)->execute(
                new UpdateLevelingConfigDataInput(10),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateLevelingConfigValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('xpPerWorkoutMinute', $e->violations);
        }

        // The rejected update must not have touched the singleton.
        self::assertSame(50, $container->get(LevelingConfigProviderGateway::class)->getSingleton()->xpPerWorkoutMinute);
    }
}
