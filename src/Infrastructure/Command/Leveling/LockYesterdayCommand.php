<?php

declare(strict_types=1);

namespace App\Infrastructure\Command\Leveling;

use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use App\Domain\Service\Leveling\LevelingCalculator;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Nightly leveling cron: folds every still-unlocked `EarnedExperience` earned before today
 * (00:00 Europe/Paris) into its player's level/XP, then locks the consumed entries so they are
 * never counted twice. Idempotent — a second run finds nothing left to lock (and same-day entries,
 * `earnedAt >= cutoff`, are deliberately left for the following night). The `--cutoff` override is a
 * debug/testing affordance; production runs rely on the wall clock.
 */
#[AsCommand(
    name: 'app:leveling:lock-yesterday',
    description: 'Locks yesterday\'s earned experience and advances each player\'s level.',
)]
final class LockYesterdayCommand extends Command
{
    public function __construct(
        private readonly EarnedExperienceProviderGateway $earnedExperienceProvider,
        private readonly EarnedExperiencePersisterGateway $earnedExperiencePersister,
        private readonly PlayerPersisterGateway $playerPersister,
        private readonly LevelingCalculator $levelingCalculator,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'cutoff',
            null,
            InputOption::VALUE_REQUIRED,
            'ISO-8601 cutoff override (debug/testing); defaults to today 00:00 Europe/Paris.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cutoffOption = $input->getOption('cutoff');
        if (null !== $cutoffOption) {
            try {
                $cutoff = new \DateTimeImmutable((string) $cutoffOption);
            } catch (\Exception) {
                $io->error(sprintf('Invalid --cutoff value "%s": expected an ISO-8601 datetime.', (string) $cutoffOption));

                return Command::INVALID;
            }
        } else {
            $cutoff = $this->clock->now()
                ->setTimezone(new \DateTimeZone('Europe/Paris'))
                ->setTime(0, 0, 0);
        }

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

        $io->success(sprintf(
            'Locked %d entries across %d players, awarding %d XP (cutoff %s).',
            $entriesLocked,
            $playersTouched,
            $totalXpAwarded,
            $cutoff->format(\DateTimeInterface::ATOM),
        ));

        return Command::SUCCESS;
    }
}
