<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutsByMonthDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\ListWorkoutsByMonthValidator;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\ListWorkoutsByMonthUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListWorkoutsByMonthUseCaseTest extends KernelTestCase
{
    public function testItReturnsEveryStatusFallingInTheGivenMonth(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'calendar-mix');
        $persister = self::buildWorkoutPersister($container);

        // April 2026: one PLANNED, one COMPLETED, one CANCELED — all should appear.
        $planned = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $planned->plannedAt = new \DateTimeImmutable('2026-04-15T10:00:00', new \DateTimeZone('UTC'));
        $persister->create($planned);

        $completed = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $completed->dateStart = new \DateTimeImmutable('2026-04-20T08:00:00', new \DateTimeZone('UTC'));
        $completed->dateEnd = new \DateTimeImmutable('2026-04-20T09:00:00', new \DateTimeZone('UTC'));
        $persister->create($completed);

        $canceled = new WorkoutDataModel($player, WorkoutStatusRegistry::CANCELED);
        $canceled->plannedAt = new \DateTimeImmutable('2026-04-25T18:00:00', new \DateTimeZone('UTC'));
        $persister->create($canceled);

        // Boundary noise: a March workout and a May workout — neither must appear.
        $march = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $march->dateStart = new \DateTimeImmutable('2026-03-31T22:00:00', new \DateTimeZone('UTC'));
        $march->dateEnd = new \DateTimeImmutable('2026-03-31T23:00:00', new \DateTimeZone('UTC'));
        $persister->create($march);

        $may = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $may->plannedAt = new \DateTimeImmutable('2026-05-01T00:00:00', new \DateTimeZone('UTC'));
        $persister->create($may);

        $output = self::buildUseCase($container, $player)->execute(new ListWorkoutsByMonthDataInput(2026, 4));

        self::assertCount(3, $output);
        $ids = array_map(static fn ($w) => $w->id, $output);
        self::assertContains($planned->id, $ids);
        self::assertContains($completed->id, $ids);
        self::assertContains($canceled->id, $ids);
        self::assertNotContains($march->id, $ids);
        self::assertNotContains($may->id, $ids);
    }

    public function testItExcludesDeletedWorkouts(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'calendar-deleted');
        $persister = self::buildWorkoutPersister($container);

        $kept = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $kept->plannedAt = new \DateTimeImmutable('2026-04-15T10:00:00', new \DateTimeZone('UTC'));
        $persister->create($kept);

        $deleted = new WorkoutDataModel($player, WorkoutStatusRegistry::DELETED);
        $deleted->plannedAt = new \DateTimeImmutable('2026-04-18T10:00:00', new \DateTimeZone('UTC'));
        $persister->create($deleted);

        $output = self::buildUseCase($container, $player)->execute(new ListWorkoutsByMonthDataInput(2026, 4));

        self::assertCount(1, $output);
        self::assertSame($kept->id, $output[0]->id);
    }

    public function testItPicksTheMostAdvancedDateForOrdering(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'calendar-order');
        $persister = self::buildWorkoutPersister($container);

        // Workout originally planned 2026-04-05 but completed 2026-04-25 — must be
        // bucketed by `dateEnd` (the most advanced of the three), so it lands in April
        // and orders accordingly.
        $late = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $late->plannedAt = new \DateTimeImmutable('2026-04-05T10:00:00', new \DateTimeZone('UTC'));
        $late->dateStart = new \DateTimeImmutable('2026-04-25T08:00:00', new \DateTimeZone('UTC'));
        $late->dateEnd = new \DateTimeImmutable('2026-04-25T09:00:00', new \DateTimeZone('UTC'));
        $persister->create($late);

        $early = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $early->plannedAt = new \DateTimeImmutable('2026-04-10T10:00:00', new \DateTimeZone('UTC'));
        $persister->create($early);

        $output = self::buildUseCase($container, $player)->execute(new ListWorkoutsByMonthDataInput(2026, 4));

        self::assertCount(2, $output);
        self::assertSame($early->id, $output[0]->id);
        self::assertSame($late->id, $output[1]->id);
    }

    public function testItReturnsEmptyForAMonthWithNoWorkouts(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'calendar-empty');
        $persister = self::buildWorkoutPersister($container);

        $persister->create((function () use ($player) {
            $w = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
            $w->plannedAt = new \DateTimeImmutable('2026-04-15T10:00:00', new \DateTimeZone('UTC'));

            return $w;
        })());

        $output = self::buildUseCase($container, $player)->execute(new ListWorkoutsByMonthDataInput(2026, 7));

        self::assertSame([], $output);
    }

    public function testItExcludesAnotherPlayersWorkouts(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $playerA = self::createTestPlayer($container, 'calendar-isolation-a');
        $playerB = self::createTestPlayer($container, 'calendar-isolation-b');
        $persister = self::buildWorkoutPersister($container);

        $a = new WorkoutDataModel($playerA, WorkoutStatusRegistry::PLANNED);
        $a->plannedAt = new \DateTimeImmutable('2026-04-15T10:00:00', new \DateTimeZone('UTC'));
        $persister->create($a);
        $b = new WorkoutDataModel($playerB, WorkoutStatusRegistry::PLANNED);
        $b->plannedAt = new \DateTimeImmutable('2026-04-15T10:00:00', new \DateTimeZone('UTC'));
        $persister->create($b);

        $output = self::buildUseCase($container, $playerA)->execute(new ListWorkoutsByMonthDataInput(2026, 4));

        self::assertCount(1, $output);
        self::assertSame($a->id, $output[0]->id);
    }

    public function testItRejectsAnInvalidMonth(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'calendar-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new ListWorkoutsByMonthDataInput(2026, 13));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListWorkoutsByMonthValidator::ERROR_CODE, $e->errorCode);
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

    private static function buildWorkoutPersister(ContainerInterface $container): WorkoutPersister
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);

        return new WorkoutPersister($em, $clock);
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListWorkoutsByMonthUseCase
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

        return new ListWorkoutsByMonthUseCase(
            new ListWorkoutsByMonthValidator(),
            $resolver,
            new WorkoutRepository($registry),
            self::getContainer()->get(ObjectMapperInterface::class),
        );
    }
}
