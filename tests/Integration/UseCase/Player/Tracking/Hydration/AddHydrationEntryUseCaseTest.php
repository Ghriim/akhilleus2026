<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\AddHydrationEntryDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Hydration\AddHydrationEntryValidator;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationDailySummaryPersister;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationEntryPersister;
use App\Infrastructure\Repository\Tracking\Hydration\HydrationDailySummaryRepository;
use App\UseCase\Player\Tracking\Hydration\AddHydrationEntryUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AddHydrationEntryUseCaseTest extends KernelTestCase
{
    public function testItLazyCreatesTheSummaryAndRecomputesTheAggregate(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'add-hydration-create');
        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T09:00:00Z'), 250));

        self::assertSame(1000, $output->targetMl);
        self::assertSame(250, $output->amountConsumedMl);
        self::assertCount(1, $output->entries);
        self::assertSame(250, $output->entries[0]->valueMl);
    }

    public function testItAddsSeveralEntriesToTheSameDayAndSumsThem(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'add-hydration-sum');
        $useCase = self::buildUseCase($container, $player);

        $useCase->execute(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T09:00:00Z'), 250));
        $useCase->execute(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T12:30:00Z'), 500));
        $output = $useCase->execute(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T18:00:00Z'), 300));

        self::assertSame(1050, $output->amountConsumedMl);
        self::assertCount(3, $output->entries);
    }

    public function testItBackfillsAnEntryOnAPastDay(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'add-hydration-backfill');
        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-01-15T08:00:00Z'), 600));

        self::assertSame(600, $output->amountConsumedMl);
        self::assertSame(
            (new \DateTimeImmutable('2026-01-15'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output->date,
        );

        $repo = new HydrationDailySummaryRepository($container->get(ManagerRegistry::class));
        self::assertNotNull($repo->findOneByPlayerAndDateWithEntries($player, new \DateTimeImmutable('2026-01-15')));
    }

    public function testItRejectsANonPositiveValue(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'add-hydration-invalid');
        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T09:00:00Z'), 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddHydrationEntryValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueMl', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): AddHydrationEntryUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);

        return new AddHydrationEntryUseCase(
            new AddHydrationEntryValidator($resolver),
            $resolver,
            new HydrationDailySummaryRepository($registry),
            $summaryPersister,
            new HydrationEntryPersister($em, $clock, $summaryPersister),
            $container->get(QuestProgressionEvaluator::class),
            $clock,
        );
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Hydration Hero',
        ));
    }

    private static function stubResolver(PlayerDataModel $player): LoggedPlayerResolverInterface
    {
        return new class ($player) implements LoggedPlayerResolverInterface {
            public function __construct(private PlayerDataModel $player)
            {
            }

            public function getLoggedPlayer(): PlayerDataModel
            {
                return $this->player;
            }
        };
    }
}
