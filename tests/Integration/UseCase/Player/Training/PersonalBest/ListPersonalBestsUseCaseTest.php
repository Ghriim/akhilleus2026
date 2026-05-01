<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\PersonalBest;

use App\Domain\DTO\DataInput\Player\Training\PersonalBest\ListPersonalBestsDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\PersonalBestTypeRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Infrastructure\Persister\Training\Workout\PersonalBestPersister;
use App\Infrastructure\Repository\Training\Workout\PersonalBestRepository;
use App\UseCase\Player\Training\PersonalBest\ListPersonalBestsUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListPersonalBestsUseCaseTest extends KernelTestCase
{
    public function testItReturnsPBsGroupedByMovement(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'pb-list');
        $bench = self::createTestMovement($container, 'pb-bench');
        $squat = self::createTestMovement($container, 'pb-squat');

        self::seedPB($container, $player, $bench, PersonalBestTypeRegistry::HIGHEST_WEIGHT, '120.0000');
        self::seedPB($container, $player, $bench, PersonalBestTypeRegistry::HIGHEST_REPS, '12.0000');
        self::seedPB($container, $player, $squat, PersonalBestTypeRegistry::HIGHEST_WEIGHT, '180.0000');

        $output = self::buildUseCase($container, $player)->execute(new ListPersonalBestsDataInput());

        self::assertCount(2, $output);
        $byMovement = [];
        foreach ($output as $bucket) {
            $byMovement[$bucket->movement->id] = $bucket;
        }
        self::assertArrayHasKey($bench->id, $byMovement);
        self::assertArrayHasKey($squat->id, $byMovement);
        self::assertCount(2, $byMovement[$bench->id]->personalBests);
        self::assertCount(1, $byMovement[$squat->id]->personalBests);
    }

    public function testItReturnsAnEmptyListWhenNoPBExists(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'pb-empty');

        $output = self::buildUseCase($container, $player)->execute(new ListPersonalBestsDataInput());

        self::assertSame([], $output);
    }

    public function testItExcludesAnotherPlayersPBs(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createTestPlayer($container, 'pb-iso-a');
        $playerB = self::createTestPlayer($container, 'pb-iso-b');
        $movement = self::createTestMovement($container, 'pb-iso-mvt');

        self::seedPB($container, $playerA, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT, '100.0000');
        self::seedPB($container, $playerB, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT, '200.0000');

        $output = self::buildUseCase($container, $playerA)->execute(new ListPersonalBestsDataInput());

        self::assertCount(1, $output);
        self::assertSame('100.0000', $output[0]->personalBests[0]->value);
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'PB Hero',
        ));
    }

    private static function createTestMovement(ContainerInterface $container, string $labelSuffix): MovementDataModel
    {
        $muscle = $container->get(MusclePersisterGateway::class)->create(new MuscleDataModel('Test muscle '.$labelSuffix));
        $movement = new MovementDataModel('Test '.$labelSuffix, $muscle);
        $movement->tracksRepetitions = true;
        $movement->tracksWeight = true;

        return $container->get(MovementPersisterGateway::class)->create($movement);
    }

    /**
     * @param numeric-string $value
     */
    private static function seedPB(ContainerInterface $container, PlayerDataModel $player, MovementDataModel $movement, string $type, string $value): void
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);
        $persister = new PersonalBestPersister($em, $clock);

        $persister->create(new PersonalBestDataModel($player, $movement, $type, $value, $clock->now()));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListPersonalBestsUseCase
    {
        $registry = $container->get(ManagerRegistry::class);
        $resolver = new class ($player) implements LoggedPlayerResolverInterface {
            public function __construct(private PlayerDataModel $player)
            {
            }

            public function getLoggedPlayer(): PlayerDataModel
            {
                return $this->player;
            }
        };

        return new ListPersonalBestsUseCase($resolver, new PersonalBestRepository($registry));
    }
}
