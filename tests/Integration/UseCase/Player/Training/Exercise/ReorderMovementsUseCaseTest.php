<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\ReorderMovementsDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\ReorderMovementsValidator;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\ExerciseRepository;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Exercise\ReorderMovementsUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReorderMovementsUseCaseTest extends KernelTestCase
{
    public function testItReordersExercisesByIndex(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'reorder-happy');
        [$workout, $exercises] = self::createTestWorkoutWithExercises($container, $player, 3);

        $useCase = self::buildUseCase($container, $player);
        $newOrder = [$exercises[2]->id, $exercises[0]->id, $exercises[1]->id];

        $output = $useCase->execute(new ReorderMovementsDataInput($workout->id, $newOrder));

        self::assertCount(3, $output);
        self::assertSame($exercises[2]->id, $output[0]->id);
        self::assertSame(0, $output[0]->position);
        self::assertSame($exercises[0]->id, $output[1]->id);
        self::assertSame(1, $output[1]->position);
        self::assertSame($exercises[1]->id, $output[2]->id);
        self::assertSame(2, $output[2]->position);
    }

    public function testItRejectsAnIdsListThatDoesNotMatchTheWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'reorder-mismatch');
        [$workout, $exercises] = self::createTestWorkoutWithExercises($container, $player, 2);

        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new ReorderMovementsDataInput($workout->id, [$exercises[0]->id, '00000000000000000000000000']));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ReorderMovementsUseCase::MISMATCH_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('orderedExerciseIds', $e->violations);
        }
    }

    public function testItRejectsACompletedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'reorder-completed');
        [$workout, $exercises] = self::createTestWorkoutWithExercises($container, $player, 2, WorkoutStatusRegistry::COMPLETED);

        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new ReorderMovementsDataInput($workout->id, [$exercises[1]->id, $exercises[0]->id]));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ReorderMovementsValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
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
     * @return array{WorkoutDataModel, list<ExerciseDataModel>}
     */
    private static function createTestWorkoutWithExercises(ContainerInterface $container, PlayerDataModel $player, int $count, string $status = WorkoutStatusRegistry::IN_PROGRESS): array
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

        $exercises = [];
        for ($i = 0; $i < $count; ++$i) {
            $exercises[] = $exercisePersister->create(new ExerciseDataModel($workout, $movement, $i, 30));
        }

        return [$workout, $exercises];
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ReorderMovementsUseCase
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

        return new ReorderMovementsUseCase(
            new ReorderMovementsValidator($resolver),
            $resolver,
            new WorkoutRepository($registry),
            new ExerciseRepository($registry),
            new ExercisePersister($em, $clock),
        );
    }
}
