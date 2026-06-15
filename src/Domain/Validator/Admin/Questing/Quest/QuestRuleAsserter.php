<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Questing\Quest;

use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;

/**
 * Shared `kind ⇔ metric ⇔ targetValue` + field invariants for the admin Create/Update quest
 * validators. Follows the `AddExerciseSetValidator` precedent: distinct error codes for the coupling
 * rule families (thrown early), then an umbrella code for the remaining field-level violations.
 * Only the umbrella code differs between Create and Update, so it is passed in.
 */
final readonly class QuestRuleAsserter
{
    public const string KIND_METRIC_MISMATCH_CODE = 'QUEST_KIND_METRIC_MISMATCH';
    public const string TARGET_VALUE_MISMATCH_CODE = 'QUEST_TARGET_VALUE_MISMATCH';

    public static function assert(
        string $kind,
        ?string $metric,
        ?string $targetValue,
        string $periodicity,
        int $rewardedXp,
        \DateTimeImmutable $dateStart,
        ?\DateTimeImmutable $dateEnd,
        string $umbrellaErrorCode,
    ): void {
        $isAutomatic = QuestKindRegistry::AUTOMATIC === $kind;

        // 1. metric ⇔ kind coupling.
        $metricViolations = [];
        if ($isAutomatic) {
            if (null === $metric) {
                $metricViolations['metric'][] = 'An automatic quest requires a metric.';
            } elseif (false === in_array($metric, QuestMetricRegistry::ALL, true)) {
                $metricViolations['metric'][] = sprintf('Metric must be one of: %s.', implode(', ', QuestMetricRegistry::ALL));
            }
        } elseif (null !== $metric) {
            $metricViolations['metric'][] = 'A manual quest cannot carry a metric.';
        }
        if ([] !== $metricViolations) {
            throw new ValidationException('Quest metric does not match its kind.', $metricViolations, self::KIND_METRIC_MISMATCH_CODE);
        }

        // 2. targetValue ⇔ kind coupling.
        $targetViolations = [];
        if ($isAutomatic) {
            if (null === $targetValue) {
                $targetViolations['targetValue'][] = 'An automatic quest requires a target value.';
            } elseif (false === self::isPositiveNumericString($targetValue)) {
                $targetViolations['targetValue'][] = 'Target value must be a positive number.';
            }
        } elseif (null !== $targetValue) {
            $targetViolations['targetValue'][] = 'A manual quest cannot carry a target value.';
        }
        if ([] !== $targetViolations) {
            throw new ValidationException('Quest target value does not match its kind.', $targetViolations, self::TARGET_VALUE_MISMATCH_CODE);
        }

        // 3. Remaining field-level invariants under the umbrella code.
        $violations = [];
        if (false === in_array($kind, QuestKindRegistry::ALL, true)) {
            $violations['kind'][] = sprintf('Kind must be one of: %s.', implode(', ', QuestKindRegistry::ALL));
        }
        if (false === in_array($periodicity, QuestPeriodicityRegistry::ALL, true)) {
            $violations['periodicity'][] = sprintf('Periodicity must be one of: %s.', implode(', ', QuestPeriodicityRegistry::ALL));
        }
        if (0 >= $rewardedXp) {
            $violations['rewardedXp'][] = 'Rewarded XP must be strictly positive.';
        }
        if (null !== $dateEnd && $dateEnd <= $dateStart) {
            $violations['dateEnd'][] = 'End date must be strictly after the start date.';
        }
        if ([] !== $violations) {
            throw new ValidationException('Quest data is invalid.', $violations, $umbrellaErrorCode);
        }
    }

    private static function isPositiveNumericString(string $value): bool
    {
        return 1 === preg_match('/^\d+(\.\d+)?$/', $value) && 0 < (float) $value;
    }
}
