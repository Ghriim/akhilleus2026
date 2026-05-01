<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\CancelWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\CancelWorkoutValidator;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\CancelWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CancelWorkoutUseCaseTest extends KernelTestCase
{
    public function testItCancelsAPlannedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'cancel-planned');
        [$workoutPersister, $workoutRepository, $clock] = self::buildPersistenceLayer($container);

        $planned = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $planned->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($planned);

        $useCase = self::buildUseCase($player, $workoutPersister, $workoutRepository);

        $output = $useCase->execute(new CancelWorkoutDataInput($planned->id));

        self::assertSame(WorkoutStatusRegistry::CANCELED, $output->status);

        $reloaded = $workoutRepository->findOneByIdForPlayerAction($planned->id, $player);
        self::assertNotNull($reloaded);
        self::assertSame(WorkoutStatusRegistry::CANCELED, $reloaded->status);
    }

    public function testItCancelsAnInProgressWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'cancel-in-progress');
        [$workoutPersister, $workoutRepository, $clock] = self::buildPersistenceLayer($container);

        $inProgress = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $inProgress->dateStart = $clock->now();
        $workoutPersister->create($inProgress);

        $useCase = self::buildUseCase($player, $workoutPersister, $workoutRepository);

        $output = $useCase->execute(new CancelWorkoutDataInput($inProgress->id));

        self::assertSame(WorkoutStatusRegistry::CANCELED, $output->status);
    }

    public function testItThrowsNotFoundForAnUnknownId(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'cancel-not-found');
        [$workoutPersister, $workoutRepository] = self::buildPersistenceLayer($container);

        $useCase = self::buildUseCase($player, $workoutPersister, $workoutRepository);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new CancelWorkoutDataInput('00000000000000000000000000'));
    }

    public function testItRejectsAWorkoutInACancellableState(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'cancel-completed');
        [$workoutPersister, $workoutRepository, $clock] = self::buildPersistenceLayer($container);

        $completed = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $completed->dateStart = $clock->now()->modify('-1 hour');
        $completed->dateEnd = $clock->now();
        $workoutPersister->create($completed);

        $useCase = self::buildUseCase($player, $workoutPersister, $workoutRepository);

        try {
            $useCase->execute(new CancelWorkoutDataInput($completed->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CancelWorkoutValidator::ERROR_CODE, $e->errorCode);
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
     * @return array{WorkoutPersister, WorkoutRepository, ClockInterface}
     */
    private static function buildPersistenceLayer(ContainerInterface $container): array
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return [new WorkoutPersister($em, $clock), new WorkoutRepository($registry), $clock];
    }

    private static function buildUseCase(
        PlayerDataModel $player,
        WorkoutPersister $workoutPersister,
        WorkoutRepository $workoutRepository,
    ): CancelWorkoutUseCase {
        $resolver = self::stubResolver($player);

        return new CancelWorkoutUseCase(
            new CancelWorkoutValidator($resolver),
            $resolver,
            $workoutRepository,
            $workoutPersister,
        );
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
