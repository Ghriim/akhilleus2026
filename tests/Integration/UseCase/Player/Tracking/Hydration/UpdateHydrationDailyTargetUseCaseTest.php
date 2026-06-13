<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationDailyTargetDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\UpdateHydrationDailyTargetValidator;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationDailySummaryPersister;
use App\Infrastructure\Persister\Tracking\Hydration\HydrationEntryPersister;
use App\Infrastructure\Repository\Tracking\Hydration\HydrationDailySummaryRepository;
use App\UseCase\Player\Tracking\Hydration\UpdateHydrationDailyTargetUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateHydrationDailyTargetUseCaseTest extends KernelTestCase
{
    public function testItLazyCreatesTodaySummaryWithTheGivenTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-target-lazy');
        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new UpdateHydrationDailyTargetDataInput(3000));

        self::assertSame(3000, $output->targetMl);
        self::assertSame(0, $output->amountConsumedMl);
        self::assertSame([], $output->entries);
    }

    public function testItOverridesTheTargetOnAnExistingSummaryAndPreservesEntries(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);
        $em = $container->get('doctrine.orm.entity_manager');

        $player = self::createTestPlayer($container, 'update-target-existing');

        $summaryPersister = new HydrationDailySummaryPersister($em, $clock);
        $entryPersister = new HydrationEntryPersister($em, $clock, $summaryPersister);
        $summary = $summaryPersister->create(new HydrationDailySummaryDataModel($player, $clock->now()->setTime(0, 0, 0), 1000));
        $entryPersister->create(new HydrationEntryDataModel($summary, $clock->now(), 500));

        $output = self::buildUseCase($container, $player)->execute(new UpdateHydrationDailyTargetDataInput(2500));

        self::assertSame(2500, $output->targetMl);
        self::assertSame(500, $output->amountConsumedMl);
        self::assertCount(1, $output->entries);

        // The player global default is untouched by a per-day override.
        self::assertSame(1000, $player->dailyHydrationTargetMl);
    }

    public function testItRejectsANonPositiveTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-target-invalid');
        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new UpdateHydrationDailyTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateHydrationDailyTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMl', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdateHydrationDailyTargetUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new UpdateHydrationDailyTargetUseCase(
            new UpdateHydrationDailyTargetValidator($resolver),
            $resolver,
            new HydrationDailySummaryRepository($registry),
            new HydrationDailySummaryPersister($em, $clock),
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
