<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutHistoryDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\ListWorkoutHistoryValidator;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\ListWorkoutHistoryUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListWorkoutHistoryUseCaseTest extends KernelTestCase
{
    public function testItReturnsCompletedWorkoutsOrderedByDateEndDesc(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'history-order');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $older = self::buildCompleted($player, $clock->now()->modify('-2 days'));
        $newer = self::buildCompleted($player, $clock->now()->modify('-1 hour'));
        $middle = self::buildCompleted($player, $clock->now()->modify('-1 day'));
        $workoutPersister->create($older);
        $workoutPersister->create($newer);
        $workoutPersister->create($middle);

        // Plus a non-completed workout that must NOT appear in history.
        $cancelled = new WorkoutDataModel($player, WorkoutStatusRegistry::CANCELED);
        $cancelled->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($cancelled);

        $output = self::buildUseCase($container, $player)->execute(new ListWorkoutHistoryDataInput());

        self::assertSame(3, $output->totalCount);
        self::assertCount(3, $output->items);
        self::assertSame($newer->id, $output->items[0]->id);
        self::assertSame($middle->id, $output->items[1]->id);
        self::assertSame($older->id, $output->items[2]->id);
    }

    public function testItPaginatesResults(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'history-paginate');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        for ($i = 0; 5 > $i; ++$i) {
            $workoutPersister->create(self::buildCompleted($player, $clock->now()->modify(sprintf('-%d hours', $i + 1))));
        }

        $useCase = self::buildUseCase($container, $player);

        $page1 = $useCase->execute(new ListWorkoutHistoryDataInput(1, 2));
        self::assertSame(5, $page1->totalCount);
        self::assertCount(2, $page1->items);

        $page2 = $useCase->execute(new ListWorkoutHistoryDataInput(2, 2));
        self::assertCount(2, $page2->items);
        self::assertNotSame($page1->items[0]->id, $page2->items[0]->id);

        $page3 = $useCase->execute(new ListWorkoutHistoryDataInput(3, 2));
        self::assertCount(1, $page3->items);
    }

    public function testItExcludesAnotherPlayersWorkouts(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createTestPlayer($container, 'history-isolation-a');
        $playerB = self::createTestPlayer($container, 'history-isolation-b');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $workoutPersister->create(self::buildCompleted($playerA, $clock->now()));
        $workoutPersister->create(self::buildCompleted($playerB, $clock->now()));

        $output = self::buildUseCase($container, $playerA)->execute(new ListWorkoutHistoryDataInput());

        self::assertSame(1, $output->totalCount);
    }

    public function testItRejectsInvalidPageOrPerPage(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'history-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new ListWorkoutHistoryDataInput(0, 20));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListWorkoutHistoryValidator::ERROR_CODE, $e->errorCode);
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

    private static function buildCompleted(PlayerDataModel $player, \DateTimeImmutable $dateEnd): WorkoutDataModel
    {
        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $workout->dateStart = $dateEnd->modify('-1 hour');
        $workout->dateEnd = $dateEnd;

        return $workout;
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

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListWorkoutHistoryUseCase
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

        return new ListWorkoutHistoryUseCase(
            new ListWorkoutHistoryValidator(),
            $resolver,
            new WorkoutRepository($registry),
        );
    }
}
