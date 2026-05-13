<?php

declare(strict_types=1);

namespace App\Domain\Service\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;

/**
 * Recomputes a HydrationDailySummary's `amountConsumedMl` as the sum of every linked
 * `HydrationEntry.valueMl`. Mirrors the v0 `WorkoutAggregateEvaluator` shape: stateless static
 * call that mutates the summary in-place and returns the same instance, so the call site can
 * fluently chain `persister->update($evaluator::recompute($summary))`.
 *
 * Triggered from the matching persisters (Phase 2.3): every HydrationEntry create / update /
 * delete recomputes its parent summary's aggregate, and HydrationDailySummary::update keeps the
 * aggregate consistent if the entries collection was mutated outside the entry persister path.
 */
final readonly class HydrationAggregateEvaluator
{
    public static function recompute(HydrationDailySummaryDataModel $summary): HydrationDailySummaryDataModel
    {
        $sum = 0;
        foreach ($summary->entries as $entry) {
            $sum += $entry->valueMl;
        }
        $summary->amountConsumedMl = $sum;

        return $summary;
    }
}
