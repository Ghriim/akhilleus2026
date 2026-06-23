<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\AddMovementToWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\AddMovementToWorkoutValidator;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Movement\MovementRepository;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Exercise\AddMovementToWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class AddMovementToWorkoutUseCaseTest extends KernelTestCase
{
    public function testItAddsAMovementToAnInProgressWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-movement-happy');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->dateStart = $clock->now();
        $workoutPersister->create($workout);

        $movement = self::createTestMovement($container, 'add-movement-test-1');

        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new AddMovementToWorkoutDataInput($workout->id, $movement->id, 60));

        self::assertNotEmpty($output->id);
        self::assertSame($workout->id, $output->workoutId);
        self::assertSame(0, $output->position);
        self::assertSame(60, $output->restDurationSeconds);
        self::assertSame($movement->id, $output->movement->id);
        self::assertTrue($output->movement->tracksRepetitions);
    }

    public function testItAssignsAnIncreasingPositionForEachAdd(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-movement-positions');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $workout->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($workout);

        $movement = self::createTestMovement($container, 'add-movement-positions-1');
        $useCase = self::buildUseCase($container, $player);

        $first = $useCase->execute(new AddMovementToWorkoutDataInput($workout->id, $movement->id, 30));
        $second = $useCase->execute(new AddMovementToWorkoutDataInput($workout->id, $movement->id, 30));
        $third = $useCase->execute(new AddMovementToWorkoutDataInput($workout->id, $movement->id, 30));

        self::assertSame(0, $first->position);
        self::assertSame(1, $second->position);
        self::assertSame(2, $third->position);
    }

    public function testItRejectsACompletedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-movement-completed');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $workout->dateStart = $clock->now()->modify('-1 hour');
        $workout->dateEnd = $clock->now();
        $workoutPersister->create($workout);

        $movement = self::createTestMovement($container, 'add-movement-completed-1');
        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new AddMovementToWorkoutDataInput($workout->id, $movement->id, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddMovementToWorkoutValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItThrowsNotFoundForAnUnknownMovement(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-movement-unknown-mvt');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->dateStart = $clock->now();
        $workoutPersister->create($workout);

        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new AddMovementToWorkoutDataInput($workout->id, '00000000000000000000000000', 0));
    }

    public function testItThrowsNotFoundForAnUnknownWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'add-movement-unknown-workout');
        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new AddMovementToWorkoutDataInput('00000000000000000000000000', 'whatever', 0));
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
        $muscle = $container->get(MusclePersisterGateway::class)->create(
            new MuscleDataModel('Test muscle '.$labelSuffix),
        );

        $movement = new MovementDataModel('Test '.$labelSuffix, $muscle);
        $movement->tracksRepetitions = true;
        $movement->tracksWeight = true;

        return $container->get(MovementPersisterGateway::class)->create($movement);
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

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): AddMovementToWorkoutUseCase
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

        return new AddMovementToWorkoutUseCase(
            new AddMovementToWorkoutValidator($resolver),
            $resolver,
            new WorkoutRepository($registry),
            new MovementRepository($registry),
            new ExercisePersister($em, $clock),
            self::getContainer()->get(ObjectMapperInterface::class),
        );
    }
}
