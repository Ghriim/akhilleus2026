<?php

declare(strict_types=1);

namespace App\Domain\Service\Leveling;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;

/**
 * Validates the structural invariants of a whole leveling curve (a list of `LevelBracket`s), as
 * specified in `specifications/v1/initial-requirements.md`: first bracket starts at level 1,
 * brackets are contiguous and non-overlapping, exactly one open-ended bracket (last), and the
 * marginal cost is strictly positive at every covered boundary. Shared by the admin create/update
 * validators, which build the *resulting* curve (existing brackets + the new/updated one) and ask
 * this evaluator to collect any violations. Pure / stateless — no gateway, no persistence.
 */
final readonly class LevelCurveEvaluator
{
    /**
     * Per-bracket field checks (independent of the rest of the curve): valid level range and a
     * usable exponent. Returned keyed by field so the admin form can surface them inline.
     *
     * @return array<string, list<string>>
     */
    public static function collectFieldViolations(int $fromLevel, ?int $toLevel, int $exponentK): array
    {
        $violations = [];
        if (1 > $fromLevel) {
            $violations['fromLevel'][] = 'From level must be at least 1.';
        }
        if (null !== $toLevel && $toLevel < $fromLevel) {
            $violations['toLevel'][] = 'To level must be greater than or equal to from level (or empty for an open-ended bracket).';
        }
        if (1 > $exponentK) {
            $violations['exponentK'][] = 'Exponent k must be at least 1.';
        }

        return $violations;
    }

    /**
     * @param list<LevelBracketDataModel> $brackets
     *
     * @return list<LevelBracketDataModel>
     */
    public static function sortByFromLevel(array $brackets): array
    {
        usort($brackets, static fn (LevelBracketDataModel $a, LevelBracketDataModel $b): int => $a->fromLevel <=> $b->fromLevel);

        return $brackets;
    }

    /**
     * @param list<LevelBracketDataModel> $brackets the full resulting curve, already sorted by fromLevel ASC
     *
     * @return list<string> human-readable violation messages (empty when the curve is valid)
     */
    public static function collectCurveViolations(array $brackets): array
    {
        if ([] === $brackets) {
            return [];
        }

        $violations = [];
        $count = \count($brackets);

        if (1 !== $brackets[0]->fromLevel) {
            $violations[] = 'The first bracket must start at level 1.';
        }

        $openEndedCount = 0;
        foreach ($brackets as $index => $bracket) {
            if (null === $bracket->toLevel) {
                ++$openEndedCount;
                if ($index !== $count - 1) {
                    $violations[] = 'Only the last bracket may be open-ended (toLevel = null).';
                }
            }
        }
        if (1 !== $openEndedCount) {
            $violations[] = 'Exactly one bracket must be open-ended (the last one, with toLevel = null).';
        }

        for ($i = 1; $i < $count; ++$i) {
            $previous = $brackets[$i - 1];
            $current = $brackets[$i];
            if (null === $previous->toLevel) {
                // A non-last open-ended bracket was already flagged above; contiguity is undefined here.
                continue;
            }

            if ($current->fromLevel <= $previous->toLevel) {
                $violations[] = sprintf(
                    'Brackets must not overlap: the bracket starting at level %d overlaps the previous one ending at level %d.',
                    $current->fromLevel,
                    $previous->toLevel,
                );
            } elseif ($current->fromLevel !== $previous->toLevel + 1) {
                $violations[] = sprintf(
                    'Brackets must be contiguous: the bracket after level %d must start at level %d, not %d.',
                    $previous->toLevel,
                    $previous->toLevel + 1,
                    $current->fromLevel,
                );
            }
        }

        foreach ($brackets as $bracket) {
            $boundaries = [$bracket->fromLevel];
            if (null !== $bracket->toLevel) {
                $boundaries[] = $bracket->toLevel;
            }
            foreach ($boundaries as $level) {
                if (0 >= self::marginalCost($bracket, $level)) {
                    $violations[] = sprintf(
                        'The marginal cost must be strictly positive for every covered level (it is not at level %d).',
                        $level,
                    );
                }
            }
        }

        return $violations;
    }

    private static function marginalCost(LevelBracketDataModel $bracket, int $level): int
    {
        $power = 1;
        for ($i = 0; $i < $bracket->exponentK; ++$i) {
            $power *= $level;
        }

        return $bracket->coefficientA * $power + $bracket->offsetB;
    }
}
