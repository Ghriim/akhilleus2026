<?php

declare(strict_types=1);

namespace App\Domain\Service\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;

/**
 * Computes a SleepDailyEntry's `durationMinutes` from its `bedAt` / `wakeAt` timestamps:
 * `floor((wakeAt − bedAt) / 60)`. Mutates the entry in-place and returns the same instance —
 * the matching `SleepDailyEntryPersister` calls this from `create` / `update`.
 *
 * Caller responsibility: validators on the use case ensure `wakeAt > bedAt` before this runs.
 * The service does not re-check the order — a wrongly-ordered pair will produce a non-positive
 * duration, which is acceptable as a fail-safe (visible in tests / fixtures).
 */
final readonly class SleepDurationEvaluator
{
    public static function recompute(SleepDailyEntryDataModel $entry): SleepDailyEntryDataModel
    {
        $seconds = $entry->wakeAt->getTimestamp() - $entry->bedAt->getTimestamp();
        $entry->durationMinutes = (int) floor($seconds / 60);

        return $entry;
    }
}
