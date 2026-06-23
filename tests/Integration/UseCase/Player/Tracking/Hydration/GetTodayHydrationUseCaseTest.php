<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\GetTodayHydrationDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationDailySummaryPersister;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationEntryPersister;
use App\Infrastructure\Repository\Tracking\Hydration\HydrationDailySummaryRepository;
use App\UseCase\Player\Tracking\Hydration\GetTodayHydrationUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class GetTodayHydrationUseCaseTest extends KernelTestCase
{
    public function testItLazyCreatesTodaySummaryWithThePlayerDefaultTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);

        $player = self::createTestPlayer($container, 'get-hydration-lazy');
        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new GetTodayHydrationDataInput());

        self::assertSame(1000, $output->targetMl);
        self::assertSame(0, $output->amountConsumedMl);
        self::assertSame([], $output->entries);
        self::assertSame($clock->now()->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM), $output->date);

        $repo = new HydrationDailySummaryRepository($container->get(ManagerRegistry::class));
        self::assertNotNull($repo->findOneByPlayerAndDateWithEntries($player, $clock->now()));
    }

    public function testItReturnsTheExistingSummaryWithItsEntries(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        $em = $container->get('doctrine.orm.entity_manager');

        $player = self::createTestPlayer($container, 'get-hydration-existing');

        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);
        $entryPersister = new HydrationEntryPersister($em, $clock, $summaryPersister);
        $summary = $summaryPersister->create(new HydrationDailySummaryDataModel($player, $clock->now()->setTime(0, 0, 0), 1500));
        $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 400));
        $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 350));

        $output = self::buildUseCase($container, $player)->execute(new GetTodayHydrationDataInput());

        self::assertSame(1500, $output->targetMl);
        self::assertSame(750, $output->amountConsumedMl);
        self::assertCount(2, $output->entries);
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): GetTodayHydrationUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new GetTodayHydrationUseCase(
            $resolver,
            new HydrationDailySummaryRepository($registry),
            new HydrationDailySummaryPersister($em, $clock),
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
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
