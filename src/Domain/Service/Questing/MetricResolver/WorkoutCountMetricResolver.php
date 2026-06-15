<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;

final readonly class WorkoutCountMetricResolver implements MetricResolverInterface
{
    public function __construct(
        private WorkoutProviderGateway $workoutProvider,
    ) {
    }

    public function getMetric(): string
    {
        return QuestMetricRegistry::WORKOUT_COUNT;
    }

    public function resolveCurrentValue(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        return (float) \count($this->workoutProvider->findCompletedByPlayerInRange($player, $from, $to));
    }
}
