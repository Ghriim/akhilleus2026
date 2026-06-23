<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Hydration\UpdateHydrationEntryValidator;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationDailySummaryPersister;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationEntryPersister;
use App\Infrastructure\Repository\Tracking\Hydration\HydrationEntryRepository;
use App\UseCase\Player\Tracking\Hydration\UpdateHydrationEntryUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateHydrationEntryUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTheValueAndRecomputesTheAggregate(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        $em = $container->get('doctrine.orm.entity_manager');

        $player = self::createTestPlayer($container, 'update-entry-happy');

        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);
        $entryPersister = new HydrationEntryPersister($em, $clock, $summaryPersister);
        $summary = $summaryPersister->create(new HydrationDailySummaryDataModel($player, $clock->now()->setTime(0, 0, 0), 1000));
        $first = $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 250));
        $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 500));

        $output = self::buildUseCase($container, $player)->execute(new UpdateHydrationEntryDataInput($first->id, 400));

        self::assertSame(900, $output->amountConsumedMl);
        self::assertCount(2, $output->entries);
    }

    public function testItThrowsWhenTheEntryBelongsToAnotherPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        $em = $container->get('doctrine.orm.entity_manager');

        $owner = self::createTestPlayer($container, 'update-entry-owner');
        $intruder = self::createTestPlayer($container, 'update-entry-intruder');

        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);
        $entryPersister = new HydrationEntryPersister($em, $clock, $summaryPersister);
        $summary = $summaryPersister->create(new HydrationDailySummaryDataModel($owner, $clock->now()->setTime(0, 0, 0), 1000));
        $entry = $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 250));

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $intruder)->execute(new UpdateHydrationEntryDataInput($entry->id, 400));
    }

    public function testItRejectsANonPositiveValue(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-entry-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new UpdateHydrationEntryDataInput('01HZX0000000000000000ENTRY', 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateHydrationEntryValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueMl', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdateHydrationEntryUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);

        return new UpdateHydrationEntryUseCase(
            new UpdateHydrationEntryValidator($resolver),
            $resolver,
            new HydrationEntryRepository($registry),
            new HydrationEntryPersister($em, $clock, $summaryPersister),
            $container->get(QuestProgressionEvaluator::class),
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
