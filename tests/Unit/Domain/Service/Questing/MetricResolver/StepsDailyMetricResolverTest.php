<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Service\Questing\MetricResolver\StepsDailyMetricResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class StepsDailyMetricResolverTest extends TestCase
{
    public function testItReportsItsMetric(): void
    {
        $resolver = new StepsDailyMetricResolver($this->createMock(StepsDailyEntryProviderGateway::class));

        self::assertSame(QuestMetricRegistry::STEPS_DAILY, $resolver->getMetric());
    }

    public function testItSumsTheStepCountsOverTheRange(): void
    {
        $provider = $this->createMock(StepsDailyEntryProviderGateway::class);
        $provider->method('findAllByPlayerForRange')->willReturn([
            self::entry(8000),
            self::entry(2500),
        ]);

        $resolver = new StepsDailyMetricResolver($provider);

        $value = $resolver->resolveCurrentValue(
            $this->createMock(PlayerDataModel::class),
            new \DateTimeImmutable('2026-06-15 00:00:00'),
            new \DateTimeImmutable('2026-06-15 23:59:59'),
        );

        self::assertSame(10500.0, $value);
    }

    private function entry(int $count): StepsDailyEntryDataModel
    {
        $entry = $this->createMock(StepsDailyEntryDataModel::class);
        $entry->count = $count;

        return $entry;
    }
}
