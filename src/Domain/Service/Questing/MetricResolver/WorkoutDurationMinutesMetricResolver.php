<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;

final readonly class WorkoutDurationMinutesMetricResolver implements MetricResolverInterface
{
    public function __construct(
        private WorkoutProviderGateway $workoutProvider,
    ) {
    }

    public function getMetric(): string
    {
        return QuestMetricRegistry::WORKOUT_DURATION_MINUTES;
    }

    public function resolveCurrentValue(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        $seconds = 0;
        foreach ($this->workoutProvider->findCompletedByPlayerInRange($player, $from, $to) as $workout) {
            // `duration` is stored in seconds by WorkoutAggregateEvaluator.
            $seconds += $workout->duration ?? 0;
        }

        return $seconds / 60;
    }
}
