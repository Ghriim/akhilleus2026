<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Service\Questing\MetricResolver\WorkoutCountMetricResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class WorkoutCountMetricResolverTest extends TestCase
{
    public function testItReportsItsMetric(): void
    {
        $resolver = new WorkoutCountMetricResolver($this->createMock(WorkoutProviderGateway::class));

        self::assertSame(QuestMetricRegistry::WORKOUT_COUNT, $resolver->getMetric());
    }

    public function testItCountsTheCompletedWorkoutsOverTheRange(): void
    {
        $provider = $this->createMock(WorkoutProviderGateway::class);
        $provider->method('findCompletedByPlayerInRange')->willReturn([
            $this->createMock(WorkoutDataModel::class),
            $this->createMock(WorkoutDataModel::class),
            $this->createMock(WorkoutDataModel::class),
        ]);

        $resolver = new WorkoutCountMetricResolver($provider);

        $value = $resolver->resolveCurrentValue(
            $this->createMock(PlayerDataModel::class),
            new \DateTimeImmutable('2026-06-01 00:00:00'),
            new \DateTimeImmutable('2026-06-30 23:59:59'),
        );

        self::assertSame(3.0, $value);
    }
}
