<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;

final readonly class StepsDailyMetricResolver implements MetricResolverInterface
{
    public function __construct(
        private StepsDailyEntryProviderGateway $stepsProvider,
    ) {
    }

    public function getMetric(): string
    {
        return QuestMetricRegistry::STEPS_DAILY;
    }

    public function resolveCurrentValue(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        $total = 0;
        foreach ($this->stepsProvider->findAllByPlayerForRange($player, $from, $to) as $entry) {
            $total += $entry->count;
        }

        return (float) $total;
    }
}
