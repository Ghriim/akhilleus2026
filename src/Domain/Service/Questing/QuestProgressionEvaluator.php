<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Service\Questing\MetricResolver\HydrationMlDailyMetricResolver;
use App\Domain\Service\Questing\MetricResolver\MetricResolverInterface;
use App\Domain\Service\Questing\MetricResolver\SleepDurationMinutesMetricResolver;
use App\Domain\Service\Questing\MetricResolver\StepsDailyMetricResolver;
use App\Domain\Service\Questing\MetricResolver\WorkoutCountMetricResolver;
use App\Domain\Service\Questing\MetricResolver\WorkoutDurationMinutesMetricResolver;

/**
 * Recomputes a player's progress on every active AUTOMATIC quest measuring a given metric, after a
 * tracking/workout write. Find-or-creates each quest's current-period progression, refreshes its
 * `currentValue` from the matching `MetricResolver`, and flips `IN_PROGRESS → CLAIMABLE` once the
 * target is met. Already-`REWARDED` rows are left untouched.
 *
 * The resolvers are injected explicitly (rather than via a Symfony tagged iterator) to keep this
 * Domain service free of any framework dependency.
 */
final readonly class QuestProgressionEvaluator
{
    /** @var array<string, MetricResolverInterface> */
    private array $resolversByMetric;

    public function __construct(
        private QuestProviderGateway $questProvider,
        private QuestProgressionFactory $progressionFactory,
        private QuestProgressionPersisterGateway $progressionPersister,
        StepsDailyMetricResolver $stepsResolver,
        HydrationMlDailyMetricResolver $hydrationResolver,
        SleepDurationMinutesMetricResolver $sleepResolver,
        WorkoutCountMetricResolver $workoutCountResolver,
        WorkoutDurationMinutesMetricResolver $workoutDurationResolver,
    ) {
        $map = [];
        foreach ([$stepsResolver, $hydrationResolver, $sleepResolver, $workoutCountResolver, $workoutDurationResolver] as $resolver) {
            $map[$resolver->getMetric()] = $resolver;
        }
        $this->resolversByMetric = $map;
    }

    public function refreshFor(PlayerDataModel $player, string $metric, \DateTimeImmutable $now): void
    {
        $resolver = $this->resolversByMetric[$metric] ?? null;
        if (null === $resolver) {
            return;
        }

        foreach ($this->questProvider->findActiveAutomaticByMetric($metric, $now) as $quest) {
            $progression = $this->progressionFactory->findOrCreate($quest, $player, $now);
            if (QuestProgressionStatusRegistry::REWARDED === $progression->status) {
                continue;
            }

            // UNIQUE automatic quests have no period window → measure since the quest started.
            $from = $progression->startDate ?? $quest->dateStart;
            $to = $progression->endDate ?? $now;

            $current = $resolver->resolveCurrentValue($player, $from, $to);
            $progression->currentValue = number_format($current, 4, '.', '');

            $target = null !== $quest->targetValue ? (float) $quest->targetValue : 0.0;
            if (QuestProgressionStatusRegistry::IN_PROGRESS === $progression->status && $current >= $target) {
                $progression->status = QuestProgressionStatusRegistry::CLAIMABLE;
                $progression->completionDate = $now;
            }

            $this->progressionPersister->update($progression);
        }
    }
}
