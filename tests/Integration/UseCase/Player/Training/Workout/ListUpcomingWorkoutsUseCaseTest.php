<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\ListUpcomingWorkoutsDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\ListUpcomingWorkoutsUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListUpcomingWorkoutsUseCaseTest extends KernelTestCase
{
    public function testItReturnsPlannedFirstThenInProgressAndExcludesCompleted(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'upcoming-order');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $plannedSooner = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $plannedSooner->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($plannedSooner);

        $plannedLater = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $plannedLater->plannedAt = $clock->now()->modify('+1 week');
        $workoutPersister->create($plannedLater);

        $inProgress = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $inProgress->dateStart = $clock->now()->modify('-30 minutes');
        $workoutPersister->create($inProgress);

        $completed = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $completed->dateStart = $clock->now()->modify('-1 day');
        $completed->dateEnd = $clock->now()->modify('-23 hours');
        $workoutPersister->create($completed);

        $cancelled = new WorkoutDataModel($player, WorkoutStatusRegistry::CANCELED);
        $cancelled->plannedAt = $clock->now()->modify('+2 days');
        $workoutPersister->create($cancelled);

        $output = self::buildUseCase($container, $player)->execute(new ListUpcomingWorkoutsDataInput());

        self::assertCount(3, $output);
        self::assertSame($plannedSooner->id, $output[0]->id);
        self::assertSame($plannedLater->id, $output[1]->id);
        self::assertSame($inProgress->id, $output[2]->id);
    }

    public function testItExcludesAnotherPlayersWorkouts(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createTestPlayer($container, 'upcoming-iso-a');
        $playerB = self::createTestPlayer($container, 'upcoming-iso-b');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $a = new WorkoutDataModel($playerA, WorkoutStatusRegistry::PLANNED);
        $a->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($a);

        $b = new WorkoutDataModel($playerB, WorkoutStatusRegistry::PLANNED);
        $b->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($b);

        $output = self::buildUseCase($container, $playerA)->execute(new ListUpcomingWorkoutsDataInput());

        self::assertCount(1, $output);
        self::assertSame($a->id, $output[0]->id);
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Workout Hero',
        ));
    }

    /**
     * @return array{WorkoutPersister, ClockInterface}
     */
    private static function buildWorkoutLayer(ContainerInterface $container): array
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);

        return [new WorkoutPersister($em, $clock), $clock];
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListUpcomingWorkoutsUseCase
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

        return new ListUpcomingWorkoutsUseCase($resolver, new WorkoutRepository($registry), self::getContainer()->get(ObjectMapperInterface::class));
    }
}
