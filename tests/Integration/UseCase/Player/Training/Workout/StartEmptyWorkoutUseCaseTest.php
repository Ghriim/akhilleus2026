<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartEmptyWorkoutValidator;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\StartEmptyWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StartEmptyWorkoutUseCaseTest extends KernelTestCase
{
    public function testItStartsAnEmptyWorkoutInProgressForTheLoggedPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $playerPersister = $container->get(PlayerPersisterGateway::class);
        $player = $playerPersister->create(new RegisterPlayerDataInput(
            'start-empty-workout-test@akhilleus.test',
            'StrongPass1!',
            'Workout Hero',
        ));

        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $workoutRepository = new WorkoutRepository($registry);

        $useCase = new StartEmptyWorkoutUseCase(
            new StartEmptyWorkoutValidator($resolver, $workoutRepository),
            $resolver,
            $workoutPersister,
            $clock,
        );

        $output = $useCase->execute(new StartEmptyWorkoutDataInput());

        self::assertNotEmpty($output->id);
        self::assertSame(WorkoutStatusRegistry::IN_PROGRESS, $output->status);
        self::assertNull($output->plannedAt);
        self::assertNotNull($output->dateStart);
        self::assertNull($output->dateEnd);

        $persisted = $workoutRepository->findOneByIdForPlayerAction($output->id, $player);
        self::assertNotNull($persisted);
        self::assertSame(WorkoutStatusRegistry::IN_PROGRESS, $persisted->status);
        self::assertSame($player->id, $persisted->player->id);
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
