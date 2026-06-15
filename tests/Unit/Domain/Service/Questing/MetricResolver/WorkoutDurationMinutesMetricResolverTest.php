<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Service\Questing\MetricResolver\WorkoutDurationMinutesMetricResolver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class WorkoutDurationMinutesMetricResolverTest extends TestCase
{
    public function testItReportsItsMetric(): void
    {
        $resolver = new WorkoutDurationMinutesMetricResolver($this->createMock(WorkoutProviderGateway::class));

        self::assertSame(QuestMetricRegistry::WORKOUT_DURATION_MINUTES, $resolver->getMetric());
    }

    public function testItSumsTheStoredSecondsAndConvertsToMinutes(): void
    {
        $provider = $this->createMock(WorkoutProviderGateway::class);
        // `duration` is stored in seconds; 3600 + 1800 = 5400s = 90 min.
        $provider->method('findCompletedByPlayerInRange')->willReturn([
            self::workout(3600),
            self::workout(1800),
        ]);

        $resolver = new WorkoutDurationMinutesMetricResolver($provider);

        $value = $resolver->resolveCurrentValue(
            $this->createMock(PlayerDataModel::class),
            new \DateTimeImmutable('2026-06-01 00:00:00'),
            new \DateTimeImmutable('2026-06-30 23:59:59'),
        );

        self::assertSame(90.0, $value);
    }

    private function workout(int $durationSeconds): WorkoutDataModel
    {
        $workout = $this->createMock(WorkoutDataModel::class);
        $workout->duration = $durationSeconds;

        return $workout;
    }
}
