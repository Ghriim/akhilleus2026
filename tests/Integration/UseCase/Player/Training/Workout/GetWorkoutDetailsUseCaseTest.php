<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\GetWorkoutDetailsDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\GetWorkoutDetailsUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetWorkoutDetailsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheFullyHydratedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'details-happy');
        $movement = self::createTestMovement($container, 'details-happy-mvt');
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $exercisePersister = new ExercisePersister($em, $clock);
        $exerciseSetPersister = new ExerciseSetPersister($em, $clock);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->dateStart = $clock->now()->modify('-30 minutes');
        $workoutPersister->create($workout);

        $exercise = new ExerciseDataModel($workout, $movement, 0, 60);
        $exercisePersister->create($exercise);
        $workout->exercises->add($exercise);

        $set1 = new ExerciseSetDataModel($exercise, 0);
        $set1->plannedReps = 10;
        $set1->plannedWeight = '50.00';
        $set1->achievedReps = 10;
        $set1->achievedWeight = '50.00';
        $set1->isComplete = true;
        $exerciseSetPersister->create($set1);
        $exercise->exerciseSets->add($set1);

        $set2 = new ExerciseSetDataModel($exercise, 1);
        $set2->plannedReps = 8;
        $set2->plannedWeight = '60.00';
        $exerciseSetPersister->create($set2);
        $exercise->exerciseSets->add($set2);

        $output = self::buildUseCase($container, $player)->execute(new GetWorkoutDetailsDataInput($workout->id));

        self::assertSame($workout->id, $output->id);
        self::assertSame(WorkoutStatusRegistry::IN_PROGRESS, $output->status);
        self::assertCount(1, $output->exercises);
        self::assertSame($movement->id, $output->exercises[0]->movement->id);
        self::assertCount(2, $output->exercises[0]->sets);
        self::assertSame(10, $output->exercises[0]->sets[0]->plannedReps);
        self::assertTrue($output->exercises[0]->sets[0]->isComplete);
        self::assertFalse($output->exercises[0]->sets[1]->isComplete);
    }

    public function testItThrowsNotFoundForAnUnknownId(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'details-unknown');

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $player)->execute(new GetWorkoutDetailsDataInput('00000000000000000000000000'));
    }

    public function testItThrowsNotFoundWhenWorkoutBelongsToAnotherPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createTestPlayer($container, 'details-iso-a');
        $playerB = self::createTestPlayer($container, 'details-iso-b');
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);

        $workout = new WorkoutDataModel($playerB, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->dateStart = $clock->now();
        $workoutPersister->create($workout);

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $playerA)->execute(new GetWorkoutDetailsDataInput($workout->id));
    }

    public function testItThrowsNotFoundForADeletedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'details-deleted');
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::DELETED);
        $workout->dateStart = $clock->now();
        $workoutPersister->create($workout);

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $player)->execute(new GetWorkoutDetailsDataInput($workout->id));
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Workout Hero',
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

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): GetWorkoutDetailsUseCase
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

        return new GetWorkoutDetailsUseCase($resolver, new WorkoutRepository($registry));
    }
}
