<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\DeleteWeightDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Weight\WeightEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\LogWeightValidator;
use App\Infrastructure\Persister\Tracking\Weight\WeightEntryPersister;
use App\Infrastructure\Repository\Tracking\Weight\WeightEntryRepository;
use App\UseCase\Player\Tracking\Weight\DeleteWeightUseCase;
use App\UseCase\Player\Tracking\Weight\LogWeightUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class DeleteWeightUseCaseTest extends KernelTestCase
{
    public function testItDeletesTheEntry(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'delete-weight-happy');
        $logged = self::logWeight($container, $player, '2026-05-07T08:00:00Z', 82000);

        self::buildUseCase($container, $player)->execute(new DeleteWeightDataInput($logged->id));

        $repo = new WeightEntryRepository($container->get(ManagerRegistry::class));
        self::assertNull($repo->findOneByPlayerAndDate($player, new \DateTimeImmutable('2026-05-07')));
    }

    public function testItThrowsWhenTheEntryDoesNotExist(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'delete-weight-missing');

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $player)->execute(new DeleteWeightDataInput('01HZX000000000000000WEIGHT'));
    }

    public function testItThrowsWhenTheEntryBelongsToAnotherPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $owner = self::createTestPlayer($container, 'delete-weight-owner');
        $intruder = self::createTestPlayer($container, 'delete-weight-intruder');
        $logged = self::logWeight($container, $owner, '2026-05-07T08:00:00Z', 82000);

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $intruder)->execute(new DeleteWeightDataInput($logged->id));
    }

    private static function logWeight(ContainerInterface $container, PlayerDataModel $player, string $loggedAt, int $valueGrams): WeightEntryDataOutput
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new WeightEntryRepository($registry);

        $logUseCase = new LogWeightUseCase(
            new LogWeightValidator($resolver, $repo),
            $resolver,
            new WeightEntryPersister($em, $clock),
            self::getContainer()->get(ObjectMapperInterface::class),
        );

        return $logUseCase->execute(new LogWeightDataInput(new \DateTimeImmutable($loggedAt), $valueGrams));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): DeleteWeightUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new DeleteWeightUseCase(
            $resolver,
            new WeightEntryRepository($registry),
            new WeightEntryPersister($em, $clock),
        );
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Weight Hero',
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
