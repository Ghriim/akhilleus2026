<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Service\Questing\MetricResolver\SleepDurationMinutesMetricResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class SleepDurationMinutesMetricResolverTest extends TestCase
{
    public function testItReportsItsMetric(): void
    {
        $resolver = new SleepDurationMinutesMetricResolver($this->createMock(SleepDailyEntryProviderGateway::class));

        self::assertSame(QuestMetricRegistry::SLEEP_DURATION_MINUTES, $resolver->getMetric());
    }

    public function testItSumsTheSleepDurationsOverTheRange(): void
    {
        $provider = $this->createMock(SleepDailyEntryProviderGateway::class);
        $provider->method('findAllByPlayerForRange')->willReturn([
            self::entry(420),
            self::entry(480),
        ]);

        $resolver = new SleepDurationMinutesMetricResolver($provider);

        $value = $resolver->resolveCurrentValue(
            $this->createMock(PlayerDataModel::class),
            new \DateTimeImmutable('2026-06-14 00:00:00'),
            new \DateTimeImmutable('2026-06-15 23:59:59'),
        );

        self::assertSame(900.0, $value);
    }

    private function entry(int $durationMinutes): SleepDailyEntryDataModel
    {
        $entry = $this->createMock(SleepDailyEntryDataModel::class);
        $entry->durationMinutes = $durationMinutes;

        return $entry;
    }
}
