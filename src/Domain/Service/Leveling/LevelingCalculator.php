<?php

declare(strict_types=1);

namespace App\Domain\Service\Leveling;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;

/**
 * Reads the level curve (a contiguous set of `LevelBracket`s) and turns it into XP costs.
 * Stateless beyond its gateway; the whole curve is reloaded per public call and the containing
 * bracket is resolved in memory.
 */
final readonly class LevelingCalculator
{
    public function __construct(
        private LevelBracketProviderGateway $bracketProvider,
    ) {
    }

    /**
     * Marginal XP cost of reaching `$level` from `$level - 1`, i.e. `a × level^k + b` for the
     * bracket covering `$level`.
     *
     * @throws \LogicException when no bracket covers `$level` (curve mis-configured)
     */
    public function marginalCostFor(int $level): int
    {
        return self::costFromBrackets($level, $this->bracketProvider->findAllOrderedAsc());
    }

    /**
     * Folds `$earned` XP into the player, rolling as many level-ups as it covers. Pure in-memory
     * mutation — persisting `$player` is the caller's responsibility. Used by the nightly cron
     * and Phase 5's same-day workout-edit propagation.
     */
    public function applyEarnedAmount(PlayerDataModel $player, int $earned): void
    {
        $brackets = $this->bracketProvider->findAllOrderedAsc();

        $player->currentXp += $earned;
        while ($player->currentXp >= $player->xpToNextLevel) {
            $player->currentXp -= $player->xpToNextLevel;
            ++$player->level;
            $player->xpToNextLevel = self::costFromBrackets($player->level + 1, $brackets);
        }
    }

    /**
     * @param list<LevelBracketDataModel> $brackets
     *
     * @throws \LogicException
     */
    private static function costFromBrackets(int $level, array $brackets): int
    {
        foreach ($brackets as $bracket) {
            if ($bracket->fromLevel <= $level && (null === $bracket->toLevel || $level <= $bracket->toLevel)) {
                return $bracket->coefficientA * self::intPow($level, $bracket->exponentK) + $bracket->offsetB;
            }
        }

        throw new \LogicException(sprintf('No level bracket covers level %d.', $level));
    }

    private static function intPow(int $base, int $exponent): int
    {
        $result = 1;
        for ($i = 0; $i < $exponent; ++$i) {
            $result *= $base;
        }

        return $result;
    }
}
