<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\PlanWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\PlanWorkoutValidator;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\PlanWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PlanWorkoutUseCaseTest extends KernelTestCase
{
    public function testItPlansAFutureWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'plan-workout-happy');

        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $workoutRepository = new WorkoutRepository($registry);

        $useCase = new PlanWorkoutUseCase(
            new PlanWorkoutValidator($resolver, $clock),
            $resolver,
            $workoutPersister,
        );

        $plannedAt = $clock->now()->modify('+2 days');
        $output = $useCase->execute(new PlanWorkoutDataInput($plannedAt));

        self::assertSame(WorkoutStatusRegistry::PLANNED, $output->status);
        self::assertSame($plannedAt->format(\DateTimeInterface::ATOM), $output->plannedAt);
        self::assertNull($output->dateStart);

        $persisted = $workoutRepository->findOneByIdForPlayerAction($output->id, $player);
        self::assertNotNull($persisted);
        self::assertSame(WorkoutStatusRegistry::PLANNED, $persisted->status);
    }

    public function testItRejectsAPastDate(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'plan-workout-past');

        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);

        $useCase = new PlanWorkoutUseCase(
            new PlanWorkoutValidator($resolver, $clock),
            $resolver,
            $workoutPersister,
        );

        try {
            $useCase->execute(new PlanWorkoutDataInput($clock->now()->modify('-1 hour')));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(PlanWorkoutValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('plannedAt', $e->violations);
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
