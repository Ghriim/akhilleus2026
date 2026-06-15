<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDailySummaryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;

final readonly class HydrationMlDailyMetricResolver implements MetricResolverInterface
{
    public function __construct(
        private HydrationDailySummaryProviderGateway $summaryProvider,
    ) {
    }

    public function getMetric(): string
    {
        return QuestMetricRegistry::HYDRATION_ML_DAILY;
    }

    public function resolveCurrentValue(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        $total = 0;
        foreach ($this->summaryProvider->findAllByPlayerForRange($player, $from, $to) as $summary) {
            $total += $summary->amountConsumedMl;
        }

        return (float) $total;
    }
}
