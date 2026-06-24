<?php

declare(strict_types=1);

namespace App\Infrastructure\Command\User;

use App\Domain\DTO\DataInput\User\RegisterAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\User\RegisterAdminUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Bootstraps an admin account (user with ROLE_ADMIN + its admin profile) from the CLI — meant to
 * be run once during a production deployment, where no fixtures are loaded. Thin CLI entry point:
 * it only collects input, builds the DataInput and delegates to RegisterAdminUseCase (which owns
 * validation + persistence). The password is read from a hidden, confirmed prompt unless --password
 * is passed (avoid passing it on the command line on shared hosts — it leaks into shell history).
 */
#[AsCommand(
    name: 'app:user:create-admin',
    description: 'Creates an admin account (ROLE_ADMIN) — intended for production bootstrap.',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly RegisterAdminUseCase $registerAdmin,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email address (prompted if omitted).')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Admin password (prompted hidden if omitted).')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'Admin first name.', 'Admin')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'Admin last name.', 'User')
            ->addOption('job-title', null, InputOption::VALUE_REQUIRED, 'Admin job title.', 'Administrator');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (null === $email) {
            $email = (string) $io->ask('Admin email address');
        }

        $password = $input->getOption('password');
        if (null === $password) {
            $password = (string) $io->askHidden('Admin password (input hidden)');
            if ($password !== (string) $io->askHidden('Confirm password')) {
                $io->error('Passwords do not match.');

                return Command::INVALID;
            }
        }

        try {
            $admin = $this->registerAdmin->execute(new RegisterAdminDataInput(
                trim((string) $email),
                (string) $password,
                (string) $input->getOption('first-name'),
                (string) $input->getOption('last-name'),
                (string) $input->getOption('job-title'),
                $this->clock->now(),
            ));
        } catch (ValidationException $exception) {
            $io->error($exception->getMessage());
            $io->listing(array_merge(...array_values($exception->violations)));

            return Command::INVALID;
        }

        $io->success(sprintf('Admin "%s" created with ROLE_ADMIN (id %s).', $admin->email, $admin->id));

        return Command::SUCCESS;
    }
}
