<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\RemoveMovementFromWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\RemoveMovementFromWorkoutValidator;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\ExerciseRepository;
use App\UseCase\Player\Training\Exercise\RemoveMovementFromWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RemoveMovementFromWorkoutUseCaseTest extends KernelTestCase
{
    public function testItRemovesAnExerciseFromAnInProgressWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'remove-movement-happy');

        $exercise = self::createTestExercise($container, $player, WorkoutStatusRegistry::IN_PROGRESS);
        $useCase = self::buildUseCase($container, $player);

        $useCase->execute(new RemoveMovementFromWorkoutDataInput($exercise->id));

        $em = $container->get('doctrine.orm.entity_manager');
        self::assertNull($em->getRepository(ExerciseDataModel::class)->find($exercise->id));
    }

    public function testItRejectsRemovalFromACanceledWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'remove-movement-canceled');

        $exercise = self::createTestExercise($container, $player, WorkoutStatusRegistry::CANCELED);
        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new RemoveMovementFromWorkoutDataInput($exercise->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(RemoveMovementFromWorkoutValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItThrowsNotFoundForAnUnknownExercise(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'remove-movement-not-found');
        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new RemoveMovementFromWorkoutDataInput('00000000000000000000000000'));
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Workout Hero',
        ));
    }

    private static function createTestExercise(ContainerInterface $container, PlayerDataModel $player, string $status): ExerciseDataModel
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $exercisePersister = new ExercisePersister($em, $clock);

        $workout = new WorkoutDataModel($player, $status);
        if (WorkoutStatusRegistry::PLANNED === $status) {
            $workout->plannedAt = $clock->now()->modify('+1 day');
        } else {
            $workout->dateStart = $clock->now();
        }
        if (WorkoutStatusRegistry::COMPLETED === $status) {
            $workout->dateEnd = $clock->now();
        }
        $workoutPersister->create($workout);

        $muscle = $container->get(MusclePersisterGateway::class)->create(new MuscleDataModel('Test muscle '.$status));
        $movement = new MovementDataModel('Test movement '.$status, $muscle);
        $movement->tracksRepetitions = true;
        $container->get(MovementPersisterGateway::class)->create($movement);

        return $exercisePersister->create(new ExerciseDataModel($workout, $movement, 0, 30));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): RemoveMovementFromWorkoutUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        $resolver = new class ($player) implements LoggedPlayerResolverInterface {
            public function __construct(private PlayerDataModel $player)
            {
            }

            public function getLoggedPlayer(): PlayerDataModel
            {
                return $this->player;
            }
        };

        return new RemoveMovementFromWorkoutUseCase(
            new RemoveMovementFromWorkoutValidator($resolver),
            $resolver,
            new ExerciseRepository($registry),
            new ExercisePersister($em, $clock),
        );
    }
}
