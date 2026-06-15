<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Domain\Gateway\Provider\Questing\QuestProgression\QuestProgressionProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Service\Questing\QuestPeriodResolver;
use App\Domain\Service\Questing\QuestProgressionFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class QuestProgressionFactoryTest extends TestCase
{
    private QuestProgressionProviderGateway&MockObject $provider;
    private QuestProgressionPersisterGateway&MockObject $persister;
    private QuestProgressionFactory $factory;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(QuestProgressionProviderGateway::class);
        $this->persister = $this->createMock(QuestProgressionPersisterGateway::class);
        $this->factory = new QuestProgressionFactory(new QuestPeriodResolver(), $this->provider, $this->persister);
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-06-15 10:00:00', new \DateTimeZone('UTC'));
    }

    private function quest(string $kind, ?string $metric = null, ?string $targetValue = null): QuestDataModel
    {
        return new QuestDataModel(
            'Test quest',
            $kind,
            QuestPeriodicityRegistry::DAILY,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            100,
            $metric,
            $targetValue,
        );
    }

    public function testItReturnsTheExistingProgressionWhenOneIsFound(): void
    {
        $existing = $this->createMock(QuestProgressionDataModel::class);
        $this->provider->method('findOneByPlayerQuestPeriod')->willReturn($existing);
        $this->persister->expects(self::never())->method('create');

        $result = $this->factory->findOrCreate(
            $this->quest(QuestKindRegistry::MANUAL),
            $this->createMock(PlayerDataModel::class),
            $this->now(),
        );

        self::assertSame($existing, $result);
    }

    public function testItCreatesAnInProgressZeroedProgressionForAnAutomaticQuest(): void
    {
        $this->provider->method('findOneByPlayerQuestPeriod')->willReturn(null);

        $result = $this->factory->findOrCreate(
            $this->quest(QuestKindRegistry::AUTOMATIC, QuestMetricRegistry::STEPS_DAILY, '10000'),
            $this->createMock(PlayerDataModel::class),
            $this->now(),
        );

        self::assertSame(QuestProgressionStatusRegistry::IN_PROGRESS, $result->status);
        self::assertSame('0', $result->currentValue);
        // DAILY periodicity → a non-null period window was resolved.
        self::assertNotNull($result->startDate);
        self::assertNotNull($result->endDate);
    }

    public function testItCreatesAClaimableProgressionForAManualQuest(): void
    {
        $this->provider->method('findOneByPlayerQuestPeriod')->willReturn(null);

        $result = $this->factory->findOrCreate(
            $this->quest(QuestKindRegistry::MANUAL),
            $this->createMock(PlayerDataModel::class),
            $this->now(),
        );

        self::assertSame(QuestProgressionStatusRegistry::CLAIMABLE, $result->status);
        self::assertNull($result->currentValue);
    }
}
