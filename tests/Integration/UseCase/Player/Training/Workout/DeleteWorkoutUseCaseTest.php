<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\DeleteWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
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
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use App\Domain\Gateway\Provider\Leveling\LevelingConfig\LevelingConfigProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\Domain\Registry\Training\Workout\PersonalBestTypeRegistry;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\PersonalBestEvaluator;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Training\Workout\FinishWorkoutValidator;
use App\Infrastructure\Persister\Leveling\EarnedExperience\EarnedExperiencePersister;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Persister\Training\Workout\PersonalBestPersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\PersonalBestRepository;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\DeleteWorkoutUseCase;
use App\UseCase\Player\Training\Workout\FinishWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class DeleteWorkoutUseCaseTest extends KernelTestCase
{
    public function testItHardDeletesASameDayWorkoutAndCascadesToExercisesAndSets(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'delete-cascade');
        $movement = self::createTestMovement($container, 'delete-cascade-mvt');
        $workout = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 10, 'weight' => '50.00'], ['reps' => 8, 'weight' => '60.00']]);

        $exercise = $workout->exercises->first();
        self::assertNotFalse($exercise);
        $exerciseId = $exercise->id;
        $setIds = array_map(static fn (ExerciseSetDataModel $set): string => $set->id, $exercise->exerciseSets->toArray());
        self::assertCount(2, $setIds);

        self::buildUseCase($container, $player)->execute(new DeleteWorkoutDataInput($workout->id));

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        self::assertNull($em->find(WorkoutDataModel::class, $workout->id), 'Workout must be hard-deleted.');
        self::assertNull($em->find(ExerciseDataModel::class, $exerciseId), 'Exercise must cascade-delete.');
        foreach ($setIds as $setId) {
            self::assertNull($em->find(ExerciseSetDataModel::class, $setId), 'Exercise set must cascade-delete.');
        }
    }

    public function testItDeletesTheMatchingEarnedExperienceButPreservesPersonalBests(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'delete-xp');
        $movement = self::createTestMovement($container, 'delete-xp-mvt');
        $workout = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 10, 'weight' => '50.00']]);

        // Finishing the workout grants an unlocked EarnedExperience and writes PersonalBests
        // that reference the workout + its set.
        self::buildFinishUseCase($container, $player)->execute(new FinishWorkoutDataInput($workout->id));

        $earnedProvider = $container->get(EarnedExperienceProviderGateway::class);
        self::assertNotNull($earnedProvider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $workout->id));

        self::buildUseCase($container, $player)->execute(new DeleteWorkoutDataInput($workout->id));

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();

        self::assertNull(
            $container->get(EarnedExperienceProviderGateway::class)->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $workout->id),
            'The unlocked EarnedExperience must be removed alongside the workout.',
        );

        $pbRepo = new PersonalBestRepository($container->get(ManagerRegistry::class));
        $pb = $pbRepo->findOneForPlayerMovementType($player, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT);
        self::assertNotNull($pb, 'The PersonalBest value must be preserved when its source workout is deleted.');
        self::assertSame('50.0000', $pb->value);
        self::assertNull($pb->workout, 'The dangling workout reference must be nulled (ON DELETE SET NULL).');
        self::assertNull($pb->exerciseSet, 'The dangling exercise-set reference must be nulled (ON DELETE SET NULL).');
    }

    public function testItSoftDeletesAPastDayWorkoutAndPreservesLockedExperience(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'delete-pastday');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);
        $em = $container->get('doctrine.orm.entity_manager');

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $workout->dateStart = $clock->now()->modify('-1 day')->modify('-1 hour');
        $workout->dateEnd = $clock->now()->modify('-1 day');
        $workoutPersister->create($workout);

        // A locked XP grant from the cron must survive a soft-delete untouched.
        $earnedPersister = new EarnedExperiencePersister($em, $clock);
        $locked = new EarnedExperienceDataModel($player, 'Workout: '.$workout->name, 1500, $workout->dateEnd, EarnedExperienceSourceTypeRegistry::WORKOUT, $workout->id);
        $locked->isLocked = true;
        $earnedPersister->create($locked);

        self::buildUseCase($container, $player)->execute(new DeleteWorkoutDataInput($workout->id));

        $em->clear();
        $reloaded = $em->find(WorkoutDataModel::class, $workout->id);
        self::assertNotNull($reloaded, 'A past-day workout must be kept (soft-delete).');
        self::assertSame(WorkoutStatusRegistry::DELETED, $reloaded->status);

        $earned = $container->get(EarnedExperienceProviderGateway::class)
            ->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $workout->id);
        self::assertNotNull($earned, 'The locked EarnedExperience must be preserved on a soft-delete.');
        self::assertTrue($earned->isLocked);
    }

    public function testASoftDeletedWorkoutIsNoLongerReachableForDeletion(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'delete-twice');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::COMPLETED);
        $workout->dateStart = $clock->now()->modify('-2 day');
        $workout->dateEnd = $clock->now()->modify('-2 day');
        $workoutPersister->create($workout);

        self::buildUseCase($container, $player)->execute(new DeleteWorkoutDataInput($workout->id));

        // The read gateway filters out DELETED (5.2), so a second delete sees a 404.
        $this->expectException(EntityNotFoundException::class);
        self::buildUseCase($container, $player)->execute(new DeleteWorkoutDataInput($workout->id));
    }

    public function testItThrowsNotFoundForAnUnknownWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'delete-unknown');

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $player)->execute(new DeleteWorkoutDataInput('00000000000000000000000000'));
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

    /**
     * @param list<array{reps?: int|null, weight?: numeric-string|null, isComplete?: bool}> $setSpecs
     */
    private static function seedInProgressWorkoutWithSets(
        ContainerInterface $container,
        PlayerDataModel $player,
        MovementDataModel $movement,
        array $setSpecs,
    ): WorkoutDataModel {
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);
        $em = $container->get('doctrine.orm.entity_manager');
        $exercisePersister = new ExercisePersister($em, $clock);
        $exerciseSetPersister = new ExerciseSetPersister($em, $clock);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->dateStart = $clock->now()->modify('-1 hour');
        $workoutPersister->create($workout);

        $exercise = new ExerciseDataModel($workout, $movement, 0, 60);
        $exercisePersister->create($exercise);
        $workout->exercises->add($exercise);

        foreach ($setSpecs as $i => $spec) {
            $set = new ExerciseSetDataModel($exercise, $i);
            $set->achievedReps = $spec['reps'] ?? null;
            $set->achievedWeight = $spec['weight'] ?? null;
            $set->isComplete = $spec['isComplete'] ?? true;
            $exerciseSetPersister->create($set);
            $exercise->exerciseSets->add($set);
        }

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

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): DeleteWorkoutUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $resolver = self::stubResolver($player);

        return new DeleteWorkoutUseCase(
            $resolver,
            new WorkoutRepository($registry),
            new WorkoutPersister($em, $clock),
            $container->get(EarnedExperienceProviderGateway::class),
            new EarnedExperiencePersister($em, $clock),
            $clock,
        );
    }

    private static function buildFinishUseCase(ContainerInterface $container, PlayerDataModel $player): FinishWorkoutUseCase
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $resolver = self::stubResolver($player);

        return new FinishWorkoutUseCase(
            new FinishWorkoutValidator($resolver),
            $resolver,
            new WorkoutRepository($registry),
            new WorkoutPersister($em, $clock),
            new PersonalBestPersister($em, $clock),
            new PersonalBestEvaluator(new PersonalBestRepository($registry)),
            $container->get(LevelingConfigProviderGateway::class),
            new EarnedExperiencePersister($em, $clock),
            $container->get(QuestProgressionEvaluator::class),
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
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
