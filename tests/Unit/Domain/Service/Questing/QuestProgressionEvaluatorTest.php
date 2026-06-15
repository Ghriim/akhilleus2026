<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Gateway\Provider\Questing\QuestProgression\QuestProgressionProviderGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDailySummaryProviderGateway;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Service\Questing\MetricResolver\HydrationMlDailyMetricResolver;
use App\Domain\Service\Questing\MetricResolver\SleepDurationMinutesMetricResolver;
use App\Domain\Service\Questing\MetricResolver\StepsDailyMetricResolver;
use App\Domain\Service\Questing\MetricResolver\WorkoutCountMetricResolver;
use App\Domain\Service\Questing\MetricResolver\WorkoutDurationMinutesMetricResolver;
use App\Domain\Service\Questing\QuestPeriodResolver;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Service\Questing\QuestProgressionFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class QuestProgressionEvaluatorTest extends TestCase
{
    private QuestProviderGateway&MockObject $questProvider;
    private QuestProgressionProviderGateway&MockObject $factoryProvider;
    private QuestProgressionPersisterGateway&MockObject $progressionPersister;
    private HydrationDailySummaryProviderGateway&MockObject $hydrationProvider;
    private QuestProgressionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->questProvider = $this->createMock(QuestProviderGateway::class);
        $this->factoryProvider = $this->createMock(QuestProgressionProviderGateway::class);
        $this->progressionPersister = $this->createMock(QuestProgressionPersisterGateway::class);
        $this->hydrationProvider = $this->createMock(HydrationDailySummaryProviderGateway::class);

        $factory = new QuestProgressionFactory(
            new QuestPeriodResolver(),
            $this->factoryProvider,
            $this->createMock(QuestProgressionPersisterGateway::class),
        );

        $this->evaluator = new QuestProgressionEvaluator(
            $this->questProvider,
            $factory,
            $this->progressionPersister,
            new StepsDailyMetricResolver($this->createMock(StepsDailyEntryProviderGateway::class)),
            new HydrationMlDailyMetricResolver($this->hydrationProvider),
            new SleepDurationMinutesMetricResolver($this->createMock(SleepDailyEntryProviderGateway::class)),
            new WorkoutCountMetricResolver($this->createMock(WorkoutProviderGateway::class)),
            new WorkoutDurationMinutesMetricResolver($this->createMock(WorkoutProviderGateway::class)),
        );
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-06-15 10:00:00', new \DateTimeZone('UTC'));
    }

    private function hydrationQuest(string $targetValue): QuestDataModel
    {
        return new QuestDataModel(
            'Hydrate',
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            200,
            QuestMetricRegistry::HYDRATION_ML_DAILY,
            $targetValue,
        );
    }

    private function progression(QuestDataModel $quest, string $status): QuestProgressionDataModel
    {
        return new QuestProgressionDataModel(
            $quest,
            $this->createMock(PlayerDataModel::class),
            $status,
            new \DateTimeImmutable('2026-06-15 00:00:00'),
            new \DateTimeImmutable('2026-06-15 23:59:59'),
            '0',
        );
    }

    private function stubHydrationTotal(int $amountConsumedMl): void
    {
        $summary = $this->createMock(HydrationDailySummaryDataModel::class);
        $summary->amountConsumedMl = $amountConsumedMl;
        $this->hydrationProvider->method('findAllByPlayerForRange')->willReturn([$summary]);
    }

    public function testItIsANoOpForAnUnknownMetric(): void
    {
        $this->questProvider->expects(self::never())->method('findActiveAutomaticByMetric');

        $this->evaluator->refreshFor($this->createMock(PlayerDataModel::class), 'CALORIES_BURNED', $this->now());
    }

    public function testItRefreshesTheValueAndFlipsToClaimableWhenTheTargetIsMet(): void
    {
        $quest = $this->hydrationQuest('1000');
        $progression = $this->progression($quest, QuestProgressionStatusRegistry::IN_PROGRESS);

        $this->questProvider->method('findActiveAutomaticByMetric')->willReturn([$quest]);
        $this->factoryProvider->method('findOneByPlayerQuestPeriod')->willReturn($progression);
        $this->stubHydrationTotal(1500);
        $this->progressionPersister->expects(self::once())->method('update')->with($progression);

        $this->evaluator->refreshFor($this->createMock(PlayerDataModel::class), QuestMetricRegistry::HYDRATION_ML_DAILY, $this->now());

        self::assertSame('1500.0000', $progression->currentValue);
        self::assertSame(QuestProgressionStatusRegistry::CLAIMABLE, $progression->status);
        self::assertNotNull($progression->completionDate);
    }

    public function testItRefreshesTheValueButStaysInProgressBelowTheTarget(): void
    {
        $quest = $this->hydrationQuest('2000');
        $progression = $this->progression($quest, QuestProgressionStatusRegistry::IN_PROGRESS);

        $this->questProvider->method('findActiveAutomaticByMetric')->willReturn([$quest]);
        $this->factoryProvider->method('findOneByPlayerQuestPeriod')->willReturn($progression);
        $this->stubHydrationTotal(1500);

        $this->evaluator->refreshFor($this->createMock(PlayerDataModel::class), QuestMetricRegistry::HYDRATION_ML_DAILY, $this->now());

        self::assertSame('1500.0000', $progression->currentValue);
        self::assertSame(QuestProgressionStatusRegistry::IN_PROGRESS, $progression->status);
        self::assertNull($progression->completionDate);
    }

    public function testItLeavesAlreadyRewardedProgressionsUntouched(): void
    {
        $quest = $this->hydrationQuest('1000');
        $progression = $this->progression($quest, QuestProgressionStatusRegistry::REWARDED);

        $this->questProvider->method('findActiveAutomaticByMetric')->willReturn([$quest]);
        $this->factoryProvider->method('findOneByPlayerQuestPeriod')->willReturn($progression);
        $this->progressionPersister->expects(self::never())->method('update');

        $this->evaluator->refreshFor($this->createMock(PlayerDataModel::class), QuestMetricRegistry::HYDRATION_ML_DAILY, $this->now());

        self::assertSame('0', $progression->currentValue, 'rewarded progressions are not recomputed');
        self::assertSame(QuestProgressionStatusRegistry::REWARDED, $progression->status);
    }
}
