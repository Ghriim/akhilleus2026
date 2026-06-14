<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
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
use App\Domain\Validator\Player\Training\Workout\FinishWorkoutValidator;
use App\Infrastructure\Persister\Leveling\EarnedExperience\EarnedExperiencePersister;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Persister\Training\Workout\PersonalBestPersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use App\Infrastructure\Repository\Training\Workout\PersonalBestRepository;
use App\Infrastructure\Repository\Training\Workout\WorkoutRepository;
use App\UseCase\Player\Training\Workout\FinishWorkoutUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FinishWorkoutUseCaseTest extends KernelTestCase
{
    public function testItFinishesAnInProgressWorkoutAndPersistsPersonalBests(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-happy');
        $movement = self::createTestMovement($container, 'finish-happy-mvt', tracksRepetitions: true, tracksWeight: true);
        $workout = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 10, 'weight' => '50.00'], ['reps' => 8, 'weight' => '60.00']]);

        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new FinishWorkoutDataInput($workout->id));

        self::assertSame(WorkoutStatusRegistry::COMPLETED, $output->workout->status);
        self::assertNotNull($output->workout->dateEnd);
        // dateStart = now-1h, dateEnd = now → 60 min × 50 xpPerWorkoutMinute (seeded singleton) = 3000 XP.
        self::assertSame(3000, $output->earnedXp);
        // Five PBs expected for a strength movement: weight, reps, vol_one_set, vol_workout — and that's it (no duration/distance/speed).
        $byType = [];
        foreach ($output->newPersonalBests as $pb) {
            $byType[$pb->type] = $pb;
        }
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_WEIGHT, $byType);
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_REPS, $byType);
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET, $byType);
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT, $byType);
        self::assertSame('60.0000', $byType[PersonalBestTypeRegistry::HIGHEST_WEIGHT]->value);
        self::assertSame('10.0000', $byType[PersonalBestTypeRegistry::HIGHEST_REPS]->value);
        // Workout volume = 10*50 + 8*60 = 980
        self::assertSame('980.0000', $byType[PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT]->value);

        // Re-read from DB to confirm persistence
        $registry = $container->get(ManagerRegistry::class);
        $pbRepo = new PersonalBestRepository($registry);
        $reloaded = $pbRepo->findOneForPlayerMovementType($player, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT);
        self::assertNotNull($reloaded);
        self::assertSame('60.0000', $reloaded->value);
    }

    public function testATieWithExistingPBDoesNotProduceADuplicate(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-tie');
        $movement = self::createTestMovement($container, 'finish-tie-mvt', tracksRepetitions: true, tracksWeight: true);

        // First workout sets the PB at weight=60.
        $first = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 5, 'weight' => '60.00']]);
        self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($first->id));

        // Second workout ties at weight=60.
        $second = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 5, 'weight' => '60.00']]);
        $output = self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($second->id));

        $types = array_map(static fn ($pb) => $pb->type, $output->newPersonalBests);
        self::assertNotContains(PersonalBestTypeRegistry::HIGHEST_WEIGHT, $types);
        self::assertNotContains(PersonalBestTypeRegistry::HIGHEST_REPS, $types);
        self::assertNotContains(PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET, $types);
        self::assertNotContains(PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT, $types);
    }

    public function testBeatingAnExistingPBUpdatesTheRowInPlace(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-beat');
        $movement = self::createTestMovement($container, 'finish-beat-mvt', tracksRepetitions: true, tracksWeight: true);

        $first = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 5, 'weight' => '60.00']]);
        self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($first->id));

        $registry = $container->get(ManagerRegistry::class);
        $pbRepo = new PersonalBestRepository($registry);
        $afterFirst = $pbRepo->findOneForPlayerMovementType($player, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT);
        self::assertNotNull($afterFirst);
        $originalId = $afterFirst->id;

        // Second workout beats with weight=80.
        $second = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 5, 'weight' => '80.00']]);
        self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($second->id));

        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $pbRepo = new PersonalBestRepository($registry);
        $afterSecond = $pbRepo->findOneForPlayerMovementType($player, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT);
        self::assertNotNull($afterSecond);
        self::assertSame($originalId, $afterSecond->id, 'Existing PB row must be updated in place, not duplicated.');
        self::assertSame('80.0000', $afterSecond->value);
    }

    public function testItPersistsAnUnlockedEarnedExperienceForTheCompletedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-xp');
        $movement = self::createTestMovement($container, 'finish-xp-mvt', tracksRepetitions: true, tracksWeight: true);
        $workout = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 10, 'weight' => '50.00']]);

        self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($workout->id));

        $earnedProvider = $container->get(EarnedExperienceProviderGateway::class);
        $earned = $earnedProvider->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $workout->id);
        self::assertNotNull($earned);
        self::assertSame(3000, $earned->amount);
        self::assertSame(EarnedExperienceSourceTypeRegistry::WORKOUT, $earned->sourceType);
        self::assertSame($workout->id, $earned->sourceId);
        self::assertSame($player->id, $earned->player->id);
        self::assertFalse($earned->isLocked, 'A freshly granted entry stays unlocked until the nightly cron.');
        self::assertSame('Workout: '.$workout->name, $earned->label);
    }

    public function testItGrantsNoExperienceForAZeroMinuteWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-zero-xp');
        $movement = self::createTestMovement($container, 'finish-zero-mvt', tracksRepetitions: true, tracksWeight: true);
        $clock = $container->get(ClockInterface::class);
        // dateStart = now → finish sets dateEnd = now → 0 rounded minutes → no XP, no EarnedExperience.
        $workout = self::seedInProgressWorkoutWithSets($container, $player, $movement, [['reps' => 10, 'weight' => '50.00']], $clock->now());

        $output = self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($workout->id));

        self::assertNull($output->earnedXp);
        $earned = $container->get(EarnedExperienceProviderGateway::class)
            ->findOneBySourceTypeAndId(EarnedExperienceSourceTypeRegistry::WORKOUT, $workout->id);
        self::assertNull($earned, 'A zero-minute workout must not create an EarnedExperience.');
    }

    public function testItRejectsAWorkoutWithIncompleteSets(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-incomplete');
        $movement = self::createTestMovement($container, 'finish-incomplete-mvt', tracksRepetitions: true, tracksWeight: true);
        $workout = self::seedInProgressWorkoutWithSets(
            $container,
            $player,
            $movement,
            [['reps' => 5, 'weight' => '50.00', 'isComplete' => true], ['reps' => 5, 'weight' => '50.00', 'isComplete' => false]],
        );

        try {
            self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($workout->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(FinishWorkoutValidator::INCOMPLETE_SETS_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('exerciseSets', $e->violations);
        }
    }

    public function testItRejectsAPlannedWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-planned');
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);

        $planned = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $planned->plannedAt = $clock->now()->modify('+1 day');
        $workoutPersister->create($planned);

        try {
            self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput($planned->id));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(FinishWorkoutValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
        }
    }

    public function testItThrowsNotFoundForAnUnknownWorkout(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'finish-unknown');

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $player)->execute(new FinishWorkoutDataInput('00000000000000000000000000'));
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Workout Hero',
        ));
    }

    private static function createTestMovement(
        ContainerInterface $container,
        string $labelSuffix,
        bool $tracksRepetitions = false,
        bool $tracksWeight = false,
        bool $tracksDuration = false,
        bool $tracksDistance = false,
    ): MovementDataModel {
        $muscle = $container->get(MusclePersisterGateway::class)->create(new MuscleDataModel('Test muscle '.$labelSuffix));
        $movement = new MovementDataModel('Test '.$labelSuffix, $muscle);
        $movement->tracksRepetitions = $tracksRepetitions;
        $movement->tracksWeight = $tracksWeight;
        $movement->tracksDuration = $tracksDuration;
        $movement->tracksDistance = $tracksDistance;

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
        ?\DateTimeImmutable $dateStart = null,
    ): WorkoutDataModel {
        [$workoutPersister, $clock] = self::buildWorkoutLayer($container);
        $em = $container->get('doctrine.orm.entity_manager');
        $exercisePersister = new ExercisePersister($em, $clock);
        $exerciseSetPersister = new ExerciseSetPersister($em, $clock);

        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->dateStart = $dateStart ?? $clock->now()->modify('-1 hour');
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

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): FinishWorkoutUseCase
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

        $pbRepo = new PersonalBestRepository($registry);

        return new FinishWorkoutUseCase(
            new FinishWorkoutValidator($resolver),
            $resolver,
            new WorkoutRepository($registry),
            new WorkoutPersister($em, $clock),
            new PersonalBestPersister($em, $clock),
            new PersonalBestEvaluator($pbRepo),
            $container->get(LevelingConfigProviderGateway::class),
            new EarnedExperiencePersister($em, $clock),
            $clock,
        );
    }
}
