<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training;

use App\Domain\DTO\DataInput\Player\Training\Exercise\AddMovementToWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\AddExerciseSetDataInput;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetAchievedDataInput;
use App\Domain\DTO\DataInput\Player\Training\PersonalBest\ListPersonalBestsDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\GetWorkoutDetailsDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutHistoryDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\PersonalBestTypeRegistry;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\PersonalBestEvaluator;
use App\Domain\Validator\Player\Training\Exercise\AddMovementToWorkoutValidator;
use App\Domain\Validator\Player\Training\ExerciseSet\AddExerciseSetValidator;
use App\Domain\Validator\Player\Training\ExerciseSet\UpdateExerciseSetAchievedValidator;
use App\Domain\Validator\Player\Training\Workout\FinishWorkoutValidator;
use App\Domain\Validator\Player\Training\Workout\ListWorkoutHistoryValidator;
use App\Domain\Validator\Player\Training\Workout\StartEmptyWorkoutValidator;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Persister\Training\Workout\PersonalBestPersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Movement\MovementRepository;
use App\Infrastructure\Repository\Training\Workout\ExerciseRepository;
use App\Infrastructure\Repository\Training\Workout\ExerciseSetRepository;
use App\Infrastructure\Repository\Training\Workout\PersonalBestRepository;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Exercise\AddMovementToWorkoutUseCase;
use App\UseCase\Player\Training\ExerciseSet\AddExerciseSetUseCase;
use App\UseCase\Player\Training\ExerciseSet\UpdateExerciseSetAchievedUseCase;
use App\UseCase\Player\Training\PersonalBest\ListPersonalBestsUseCase;
use App\UseCase\Player\Training\Workout\FinishWorkoutUseCase;
use App\UseCase\Player\Training\Workout\GetWorkoutDetailsUseCase;
use App\UseCase\Player\Training\Workout\ListWorkoutHistoryUseCase;
use App\UseCase\Player\Training\Workout\StartEmptyWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * End-to-end smoke test that walks the entire player workout lifecycle through the use cases:
 * start empty → add movement → add 2 sets → record achieved values → mark sets completed →
 * finish workout → assert personal bests persisted → verify the read endpoints expose everything.
 *
 * Each individual use case has its own narrow integration test elsewhere; this one exists to catch
 * composition regressions (e.g., a use case that doesn't sync inverse collections breaking the next).
 */
final class PlayerWorkoutLifecycleTest extends KernelTestCase
{
    public function testItWalksTheFullLifecycleFromStartToPersonalBestPersistence(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'lifecycle-player');
        $movement = self::createTestMovement($container, 'lifecycle-bench');
        $useCases = self::buildUseCases($container, $player);

        // 1. Start an empty workout.
        $startOutput = $useCases->startEmpty->execute(new StartEmptyWorkoutDataInput());
        self::assertSame(WorkoutStatusRegistry::IN_PROGRESS, $startOutput->status);
        $workoutId = $startOutput->id;

        // 2. Add the movement.
        $exerciseOutput = $useCases->addMovement->execute(new AddMovementToWorkoutDataInput($workoutId, $movement->id, 60));
        self::assertSame(0, $exerciseOutput->position);
        $exerciseId = $exerciseOutput->id;

        // 3. Add two sets directly with achieved values — the workout is IN_PROGRESS so the
        //    AddExerciseSet use case writes to `achieved*` (planned* is forbidden in this state).
        $set1Output = $useCases->addSet->execute(new AddExerciseSetDataInput($exerciseId, achievedReps: 10, achievedWeight: '50.00'));
        $set2Output = $useCases->addSet->execute(new AddExerciseSetDataInput($exerciseId, achievedReps: 8, achievedWeight: '62.50'));
        self::assertSame(0, $set1Output->position);
        self::assertSame(1, $set2Output->position);

        // 4. Re-record achieved on set1 to exercise the UpdateAchieved use case (idempotent).
        //    isComplete is recomputed by the use case from the achieved* values + tracking flags.
        $useCases->updateAchieved->execute(new UpdateExerciseSetAchievedDataInput($set1Output->id, achievedReps: 10, achievedWeight: '50.00'));

        // 5. Both sets were created with all required achieved* values, so they are already
        //    auto-marked isComplete=true by the AddExerciseSet use case — no manual step needed.

        // 6. Finish the workout — PBs are computed and persisted.
        $finishOutput = $useCases->finish->execute(new FinishWorkoutDataInput($workoutId));
        self::assertSame(WorkoutStatusRegistry::COMPLETED, $finishOutput->workout->status);
        self::assertNotNull($finishOutput->workout->dateEnd);

        $pbsByType = [];
        foreach ($finishOutput->newPersonalBests as $pb) {
            $pbsByType[$pb->type] = $pb;
        }
        // For a strength movement, the four PB categories that depend on (reps, weight) fire.
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_WEIGHT, $pbsByType);
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_REPS, $pbsByType);
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET, $pbsByType);
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT, $pbsByType);
        self::assertSame('62.5000', $pbsByType[PersonalBestTypeRegistry::HIGHEST_WEIGHT]->value);
        self::assertSame('10.0000', $pbsByType[PersonalBestTypeRegistry::HIGHEST_REPS]->value);
        // Best one-set volume = max(10*50, 8*62.5) = max(500, 500) = 500.
        self::assertSame('500.0000', $pbsByType[PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET]->value);
        // Workout volume = 10*50 + 8*62.5 = 500 + 500 = 1000.
        self::assertSame('1000.0000', $pbsByType[PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT]->value);

        // 7. The completed workout shows up in history (and not in upcoming, which would have to
        //    be queried separately — we only verify history here since both share the gateway).
        $history = $useCases->listHistory->execute(new ListWorkoutHistoryDataInput());
        self::assertSame(1, $history->totalCount);
        self::assertSame($workoutId, $history->items[0]->id);
        self::assertSame(WorkoutStatusRegistry::COMPLETED, $history->items[0]->status);

        // 8. Workout details reflect the full state: 1 exercise, 2 sets, all completed, achieved values present.
        $details = $useCases->getDetails->execute(new GetWorkoutDetailsDataInput($workoutId));
        self::assertCount(1, $details->exercises);
        self::assertSame($movement->id, $details->exercises[0]->movement->id);
        self::assertCount(2, $details->exercises[0]->sets);
        foreach ($details->exercises[0]->sets as $set) {
            self::assertTrue($set->isComplete);
            self::assertNotNull($set->achievedReps);
            self::assertNotNull($set->achievedWeight);
        }

        // 9. Personal-best list groups by movement and exposes the four bench-press PBs.
        $pbList = $useCases->listPersonalBests->execute(new ListPersonalBestsDataInput());
        self::assertCount(1, $pbList);
        self::assertSame($movement->id, $pbList[0]->movement->id);
        self::assertCount(4, $pbList[0]->personalBests);
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Lifecycle Hero',
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

    private static function buildUseCases(ContainerInterface $container, PlayerDataModel $player): LifecycleUseCases
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

        $workoutRepo = new WorkoutRepository($registry);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $exerciseRepo = new ExerciseRepository($registry);
        $exercisePersister = new ExercisePersister($em, $clock);
        $exerciseSetRepo = new ExerciseSetRepository($registry);
        $exerciseSetPersister = new ExerciseSetPersister($em, $clock);
        $movementRepo = new MovementRepository($registry);
        $personalBestRepo = new PersonalBestRepository($registry);
        $personalBestPersister = new PersonalBestPersister($em, $clock);

        return new LifecycleUseCases(
            startEmpty: new StartEmptyWorkoutUseCase(
                new StartEmptyWorkoutValidator($resolver, $workoutRepo),
                $resolver,
                $workoutPersister,
                $clock,
            ),
            addMovement: new AddMovementToWorkoutUseCase(
                new AddMovementToWorkoutValidator($resolver),
                $resolver,
                $workoutRepo,
                $movementRepo,
                $exercisePersister,
            ),
            addSet: new AddExerciseSetUseCase(
                new AddExerciseSetValidator($resolver),
                $resolver,
                $exerciseRepo,
                $exerciseSetRepo,
                $exerciseSetPersister,
            ),
            updateAchieved: new UpdateExerciseSetAchievedUseCase(
                new UpdateExerciseSetAchievedValidator($resolver),
                $resolver,
                $exerciseSetRepo,
                $exerciseSetPersister,
            ),
            finish: new FinishWorkoutUseCase(
                new FinishWorkoutValidator($resolver),
                $resolver,
                $workoutRepo,
                $workoutPersister,
                $personalBestPersister,
                new PersonalBestEvaluator($personalBestRepo),
                $clock,
            ),
            listHistory: new ListWorkoutHistoryUseCase(
                new ListWorkoutHistoryValidator(),
                $resolver,
                $workoutRepo,
            ),
            getDetails: new GetWorkoutDetailsUseCase($resolver, $workoutRepo),
            listPersonalBests: new ListPersonalBestsUseCase($resolver, $personalBestRepo),
        );
    }
}

/**
 * Tiny container-of-use-cases for the lifecycle test. Plain PHP class instead of an array so the
 * test body reads like prose instead of `$useCases['startEmpty']`.
 */
final readonly class LifecycleUseCases
{
    public function __construct(
        public StartEmptyWorkoutUseCase $startEmpty,
        public AddMovementToWorkoutUseCase $addMovement,
        public AddExerciseSetUseCase $addSet,
        public UpdateExerciseSetAchievedUseCase $updateAchieved,
        public FinishWorkoutUseCase $finish,
        public ListWorkoutHistoryUseCase $listHistory,
        public GetWorkoutDetailsUseCase $getDetails,
        public ListPersonalBestsUseCase $listPersonalBests,
    ) {
    }
}
