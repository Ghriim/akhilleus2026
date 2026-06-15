<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing\MetricResolver;

use App\Domain\DTO\DataModel\User\PlayerDataModel;

/**
 * Computes a player's current value for one quest metric over a `[from, to]` window. Implementations
 * depend only on Tracking / Workout provider gateways. The evaluator picks the resolver whose
 * `getMetric()` matches the quest's metric.
 */
interface MetricResolverInterface
{
    /** The `QuestMetricRegistry` value this resolver handles. */
    public function getMetric(): string;

    public function resolveCurrentValue(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): float;
}
