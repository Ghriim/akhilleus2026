<?php

declare(strict_types=1);

namespace App\Domain\Registry\Questing\Quest;

/**
 * The tracking/workout sources an `AUTOMATIC` quest can measure. Each value maps to a
 * `MetricResolver` (Phase 4.3) that computes the player's current value for a period.
 */
interface QuestMetricRegistry
{
    public const string STEPS_DAILY = 'STEPS_DAILY';
    public const string HYDRATION_ML_DAILY = 'HYDRATION_ML_DAILY';
    public const string SLEEP_DURATION_MINUTES = 'SLEEP_DURATION_MINUTES';
    public const string WORKOUT_COUNT = 'WORKOUT_COUNT';
    public const string WORKOUT_DURATION_MINUTES = 'WORKOUT_DURATION_MINUTES';

    /** @var list<string> */
    public const array ALL = [
        self::STEPS_DAILY,
        self::HYDRATION_ML_DAILY,
        self::SLEEP_DURATION_MINUTES,
        self::WORKOUT_COUNT,
        self::WORKOUT_DURATION_MINUTES,
    ];
}
