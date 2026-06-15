<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\UpdateQuestDataInput;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Validator\Admin\Questing\Quest\UpdateQuestValidator;
use App\UseCase\Admin\Questing\Quest\UpdateQuestUseCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateQuestUseCaseTest extends KernelTestCase
{
    public function testItUpdatesAQuestAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $quest = self::seedManualQuest($container);

        $useCase = $container->get(UpdateQuestUseCase::class);
        $output = $useCase->execute(new UpdateQuestDataInput(
            $quest->id,
            'Hydrate 2L',
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            200,
            QuestMetricRegistry::HYDRATION_ML_DAILY,
            '2000',
            new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
            null,
        ));

        self::assertSame($quest->id, $output->id);
        self::assertSame('Hydrate 2L', $output->label);
        self::assertSame(QuestKindRegistry::AUTOMATIC, $output->kind);
        self::assertSame(QuestMetricRegistry::HYDRATION_ML_DAILY, $output->metric);
        self::assertSame('2000', $output->targetValue);
        self::assertSame(200, $output->rewardedXp);
    }

    public function testItThrowsWhenTheQuestDoesNotExist(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $useCase = $container->get(UpdateQuestUseCase::class);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new UpdateQuestDataInput(
            '01HZX000000000000000000404',
            'Ghost',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::DAILY,
            100,
            null,
            null,
            new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
            null,
        ));
    }

    public function testItRejectsAnInvalidUpdate(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $quest = self::seedManualQuest($container);

        $useCase = $container->get(UpdateQuestUseCase::class);

        try {
            $useCase->execute(new UpdateQuestDataInput(
                $quest->id,
                'Manual with metric',
                QuestKindRegistry::MANUAL,
                QuestPeriodicityRegistry::DAILY,
                100,
                QuestMetricRegistry::STEPS_DAILY,
                null,
                new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
                null,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateQuestValidator::KIND_METRIC_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('metric', $e->violations);
        }
    }

    private static function seedManualQuest(ContainerInterface $container): QuestDataModel
    {
        return $container->get(QuestPersisterGateway::class)->create(new QuestDataModel(
            'Seed quest',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::WEEKLY,
            new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
            50,
        ));
    }
}
