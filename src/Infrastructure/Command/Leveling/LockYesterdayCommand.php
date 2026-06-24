<?php

declare(strict_types=1);

namespace App\Infrastructure\Command\Leveling;

use App\Domain\DTO\DataInput\Leveling\LockEarnedExperienceDataInput;
use App\UseCase\Leveling\LockEarnedExperienceUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Nightly leveling cron. Thin CLI entry point: it only parses the optional `--cutoff` override into
 * a `\DateTimeImmutable` (surfacing a malformed value as INVALID, the CLI equivalent of a 422) and
 * delegates to LockEarnedExperienceUseCase, which owns the day-boundary default + the lock logic.
 * The `--cutoff` override is a debug/testing affordance; production runs rely on the wall clock.
 */
#[AsCommand(
    name: 'app:leveling:lock-yesterday',
    description: 'Locks yesterday\'s earned experience and advances each player\'s level.',
)]
final class LockYesterdayCommand extends Command
{
    public function __construct(
        private readonly LockEarnedExperienceUseCase $lockEarnedExperience,
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
        $cutoff = null;
        if (null !== $cutoffOption) {
            try {
                $cutoff = new \DateTimeImmutable((string) $cutoffOption);
            } catch (\Exception) {
                $io->error(sprintf('Invalid --cutoff value "%s": expected an ISO-8601 datetime.', (string) $cutoffOption));

                return Command::INVALID;
            }
        }

        $result = $this->lockEarnedExperience->execute(new LockEarnedExperienceDataInput($cutoff));

        $io->success(sprintf(
            'Locked %d entries across %d players, awarding %d XP (cutoff %s).',
            $result->entriesLocked,
            $result->playersTouched,
            $result->totalXpAwarded,
            $result->cutoff,
        ));

        return Command::SUCCESS;
    }
}
