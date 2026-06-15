<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;

final readonly class SleepDurationMinutesMetricResolver implements MetricResolverInterface
{
    public function __construct(
        private SleepDailyEntryProviderGateway $sleepProvider,
    ) {
    }

    public function getMetric(): string
    {
        return QuestMetricRegistry::SLEEP_DURATION_MINUTES;
    }

    public function resolveCurrentValue(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        $total = 0;
        foreach ($this->sleepProvider->findAllByPlayerForRange($player, $from, $to) as $entry) {
            $total += $entry->durationMinutes;
        }

        return (float) $total;
    }
}
