<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDailySummaryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Service\Questing\MetricResolver\HydrationMlDailyMetricResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class HydrationMlDailyMetricResolverTest extends TestCase
{
    public function testItReportsItsMetric(): void
    {
        $resolver = new HydrationMlDailyMetricResolver($this->createMock(HydrationDailySummaryProviderGateway::class));

        self::assertSame(QuestMetricRegistry::HYDRATION_ML_DAILY, $resolver->getMetric());
    }

    public function testItSumsTheConsumedMillilitresOverTheRange(): void
    {
        $provider = $this->createMock(HydrationDailySummaryProviderGateway::class);
        $provider->method('findAllByPlayerForRange')->willReturn([
            self::summary(1000),
            self::summary(500),
        ]);

        $resolver = new HydrationMlDailyMetricResolver($provider);

        $value = $resolver->resolveCurrentValue(
            $this->createMock(PlayerDataModel::class),
            new \DateTimeImmutable('2026-06-15 00:00:00'),
            new \DateTimeImmutable('2026-06-15 23:59:59'),
        );

        self::assertSame(1500.0, $value);
    }

    private function summary(int $amountConsumedMl): HydrationDailySummaryDataModel
    {
        $summary = $this->createMock(HydrationDailySummaryDataModel::class);
        $summary->amountConsumedMl = $amountConsumedMl;

        return $summary;
    }
}
