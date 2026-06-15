<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\GetQuestDetailsDataInput;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\UseCase\Admin\Questing\Quest\GetQuestDetailsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetQuestDetailsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheQuestDetails(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $quest = $container->get(QuestPersisterGateway::class)->create(new QuestDataModel(
            'Walk 10k',
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
            150,
            QuestMetricRegistry::STEPS_DAILY,
            '10000',
            new \DateTimeImmutable('2026-12-31T00:00:00+00:00'),
        ));

        $useCase = $container->get(GetQuestDetailsUseCase::class);
        $output = $useCase->execute(new GetQuestDetailsDataInput($quest->id));

        self::assertSame($quest->id, $output->id);
        self::assertSame('Walk 10k', $output->label);
        self::assertSame(QuestMetricRegistry::STEPS_DAILY, $output->metric);
        self::assertSame('10000', $output->targetValue);
        self::assertNotNull($output->dateEnd);
    }

    public function testItThrowsWhenTheQuestDoesNotExist(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $useCase = $container->get(GetQuestDetailsUseCase::class);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new GetQuestDetailsDataInput('01HZX000000000000000000404'));
    }
}
