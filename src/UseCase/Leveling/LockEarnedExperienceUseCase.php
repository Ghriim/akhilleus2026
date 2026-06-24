<?php

declare(strict_types=1);

namespace App\UseCase\Leveling;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Leveling\LockEarnedExperienceDataInput;
use App\Domain\DTO\DataOutput\Leveling\LockEarnedExperienceDataOutput;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use App\Domain\Service\Leveling\LevelingCalculator;
use App\UseCase\AbstractPublicUseCase;
use Psr\Clock\ClockInterface;

/**
 * Folds every still-unlocked `EarnedExperience` earned before the cutoff (defaulting to today
 * 00:00 Europe/Paris) into its player's level/XP, then locks the consumed entries so they are never
 * counted twice. Idempotent — a second run finds nothing left to lock (and same-day entries,
 * `earnedAt >= cutoff`, are deliberately left for the following night).
 */
final readonly class LockEarnedExperienceUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private EarnedExperienceProviderGateway $earnedExperienceProvider,
        private EarnedExperiencePersisterGateway $earnedExperiencePersister,
        private PlayerPersisterGateway $playerPersister,
        private LevelingCalculator $levelingCalculator,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param LockEarnedExperienceDataInput $input
     */
    public function execute(DataInputInterface $input): LockEarnedExperienceDataOutput
    {
        $cutoff = $input->cutoff ?? $this->clock->now()
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->setTime(0, 0, 0);

        $entries = $this->earnedExperienceProvider->findUnlockedBefore($cutoff);

        $playersTouched = 0;
        $entriesLocked = 0;
        $totalXpAwarded = 0;

        // Group by player so each player's level rolls forward once over the summed amount.
        $groups = [];
        foreach ($entries as $entry) {
            $playerId = $entry->player->id;
            if (false === isset($groups[$playerId])) {
                $groups[$playerId] = ['player' => $entry->player, 'sum' => 0, 'entries' => []];
            }
            $groups[$playerId]['sum'] += $entry->amount;
            $groups[$playerId]['entries'][] = $entry;
        }

        foreach ($groups as $group) {
            $this->levelingCalculator->applyEarnedAmount($group['player'], $group['sum']);
            $this->playerPersister->update($group['player']);

            foreach ($group['entries'] as $entry) {
                $entry->isLocked = true;
                $this->earnedExperiencePersister->update($entry);
                ++$entriesLocked;
            }

            $totalXpAwarded += $group['sum'];
            ++$playersTouched;
        }

        return new LockEarnedExperienceDataOutput(
            $entriesLocked,
            $playersTouched,
            $totalXpAwarded,
            $cutoff->format(\DateTimeInterface::ATOM),
        );
    }
}
