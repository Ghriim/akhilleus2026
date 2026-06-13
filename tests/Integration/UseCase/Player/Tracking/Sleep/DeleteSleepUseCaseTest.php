<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\DeleteSleepDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\LogSleepDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\SleepDailyEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Sleep\LogSleepValidator;
use App\Infrastructure\Persister\Tracking\Sleep\SleepDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Sleep\SleepDailyEntryRepository;
use App\UseCase\Player\Tracking\Sleep\DeleteSleepUseCase;
use App\UseCase\Player\Tracking\Sleep\LogSleepUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteSleepUseCaseTest extends KernelTestCase
{
    public function testItDeletesTheEntry(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'delete-sleep-happy');
        $logged = self::logSleep($container, $player, '2026-05-06T23:00:00Z', '2026-05-07T07:00:00Z', 3);

        $output = self::buildUseCase($container, $player)->execute(new DeleteSleepDataInput($logged->id));

        self::assertSame($logged->id, $output->deletedId);

        $repo = new SleepDailyEntryRepository($container->get(ManagerRegistry::class));
        self::assertNull($repo->findOneByPlayerAndDate($player, new \DateTimeImmutable('2026-05-07')));
    }

    public function testItThrowsWhenTheEntryDoesNotExist(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'delete-sleep-missing');

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $player)->execute(new DeleteSleepDataInput('01HZX0000000000000000SLEEP'));
    }

    public function testItThrowsWhenTheEntryBelongsToAnotherPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $owner = self::createTestPlayer($container, 'delete-sleep-owner');
        $intruder = self::createTestPlayer($container, 'delete-sleep-intruder');
        $logged = self::logSleep($container, $owner, '2026-05-06T23:00:00Z', '2026-05-07T07:00:00Z', 3);

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $intruder)->execute(new DeleteSleepDataInput($logged->id));
    }

    private static function logSleep(ContainerInterface $container, PlayerDataModel $player, string $bedAt, string $wakeAt, int $quality): SleepDailyEntryDataOutput
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new SleepDailyEntryRepository($registry);

        $logUseCase = new LogSleepUseCase(
            new LogSleepValidator($resolver, $repo),
            $resolver,
            new SleepDailyEntryPersister($em, $clock),
        );

        return $logUseCase->execute(new LogSleepDataInput(
            new \DateTimeImmutable($bedAt),
            new \DateTimeImmutable($wakeAt),
            $quality,
        ));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): DeleteSleepUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new DeleteSleepUseCase(
            $resolver,
            new SleepDailyEntryRepository($registry),
            new SleepDailyEntryPersister($em, $clock),
        );
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Sleep Hero',
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
