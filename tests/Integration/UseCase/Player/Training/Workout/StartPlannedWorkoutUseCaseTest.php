<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartPlannedWorkoutValidator;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\StartPlannedWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StartPlannedWorkoutUseCaseTest extends KernelTestCase
{
    public function testItStartsAPlannedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'start-planned-happy');

        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $workoutRepository = new WorkoutRepository($registry);

        $planned = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $planned->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($planned);

        $resolver = self::stubResolver($player);
        $useCase = new StartPlannedWorkoutUseCase(
            new StartPlannedWorkoutValidator($resolver),
            $resolver,
            $workoutRepository,
            $workoutPersister,
            $clock,
        );

        $output = $useCase->execute(new StartPlannedWorkoutDataInput($planned->id));

        self::assertSame($planned->id, $output->id);
        self::assertSame(WorkoutStatusRegistry::IN_PROGRESS, $output->status);
        self::assertNotNull($output->dateStart);

        $reloaded = $workoutRepository->findOneByIdForPlayerAction($planned->id, $player);
        self::assertNotNull($reloaded);
        self::assertSame(WorkoutStatusRegistry::IN_PROGRESS, $reloaded->status);
    }

    public function testItThrowsNotFoundForAnUnknownId(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'start-planned-not-found');

        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $workoutRepository = new WorkoutRepository($registry);

        $resolver = self::stubResolver($player);
        $useCase = new StartPlannedWorkoutUseCase(
            new StartPlannedWorkoutValidator($resolver),
            $resolver,
            $workoutRepository,
            $workoutPersister,
            $clock,
        );

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new StartPlannedWorkoutDataInput('00000000000000000000000000'));
    }

    public function testItRejectsAWorkoutThatIsNotPlanned(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'start-planned-wrong-state');

        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $workoutRepository = new WorkoutRepository($registry);

        $inProgress = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $inProgress->dateStart = $clock->now();
        $workoutPersister->create($inProgress);

        $resolver = self::stubResolver($player);
        $useCase = new StartPlannedWorkoutUseCase(
            new StartPlannedWorkoutValidator($resolver),
            $resolver,
            $workoutRepository,
            $workoutPersister,
            $clock,
        );

        try {
            $useCase->execute(new StartPlannedWorkoutDataInput($inProgress->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(StartPlannedWorkoutValidator::ERROR_CODE, $e->errorCode);
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
