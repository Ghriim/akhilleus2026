<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\GetLevelingConfigDataInput;
use App\Domain\DTO\DataModel\Leveling\LevelingConfig\LevelingConfigDataModel;
use App\UseCase\Admin\Leveling\LevelingConfig\GetLevelingConfigUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetLevelingConfigUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheSeededSingleton(): void
    {
        self::bootKernel();

        $output = self::getContainer()->get(GetLevelingConfigUseCase::class)->execute(new GetLevelingConfigDataInput());

        self::assertSame(LevelingConfigDataModel::LEVELING_CONFIG_ID, $output->id);
        self::assertSame(50, $output->xpPerWorkoutMinute);
    }
}
