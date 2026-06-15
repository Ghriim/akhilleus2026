<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\DeleteHydrationEntryDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationDailySummaryPersister;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationEntryPersister;
use App\Infrastructure\Repository\Tracking\Hydration\HydrationDailySummaryRepository;
use App\Infrastructure\Repository\Tracking\Hydration\HydrationEntryRepository;
use App\UseCase\Player\Tracking\Hydration\DeleteHydrationEntryUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteHydrationEntryUseCaseTest extends KernelTestCase
{
    public function testItDeletesAnEntryAndRecomputesTheAggregate(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        $em = $container->get('doctrine.orm.entity_manager');

        $player = self::createTestPlayer($container, 'delete-entry-happy');

        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);
        $entryPersister = new HydrationEntryPersister($em, $clock, $summaryPersister);
        $summary = $summaryPersister->create(new HydrationDailySummaryDataModel($player, $clock->now()->setTime(0, 0, 0), 1000));
        $first = $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 250));
        $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 500));

        $output = self::buildUseCase($container, $player)->execute(new DeleteHydrationEntryDataInput($first->id));

        self::assertSame(500, $output->amountConsumedMl);
        self::assertCount(1, $output->entries);
    }

    public function testItLeavesAnEmptySurvivingSummaryWhenTheLastEntryIsDeleted(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        $em = $container->get('doctrine.orm.entity_manager');

        $player = self::createTestPlayer($container, 'delete-entry-last');

        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);
        $entryPersister = new HydrationEntryPersister($em, $clock, $summaryPersister);
        $summary = $summaryPersister->create(new HydrationDailySummaryDataModel($player, $clock->now()->setTime(0, 0, 0), 1000));
        $only = $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 250));

        $output = self::buildUseCase($container, $player)->execute(new DeleteHydrationEntryDataInput($only->id));

        self::assertSame(0, $output->amountConsumedMl);
        self::assertSame([], $output->entries);

        $repo = new HydrationDailySummaryRepository($container->get(ManagerRegistry::class));
        self::assertNotNull($repo->findOneByPlayerAndDateWithEntries($player, $clock->now()));
    }

    public function testItThrowsWhenTheEntryBelongsToAnotherPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        $em = $container->get('doctrine.orm.entity_manager');

        $owner = self::createTestPlayer($container, 'delete-entry-owner');
        $intruder = self::createTestPlayer($container, 'delete-entry-intruder');

        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);
        $entryPersister = new HydrationEntryPersister($em, $clock, $summaryPersister);
        $summary = $summaryPersister->create(new HydrationDailySummaryDataModel($owner, $clock->now()->setTime(0, 0, 0), 1000));
        $entry = $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 250));

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $intruder)->execute(new DeleteHydrationEntryDataInput($entry->id));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): DeleteHydrationEntryUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);

        return new DeleteHydrationEntryUseCase(
            $resolver,
            new HydrationEntryRepository($registry),
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
