<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\PersonalBest\PersonalBestProviderGateway;
use App\Domain\Registry\Training\Workout\PersonalBestTypeRegistry;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Service\PersonalBestEvaluator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class PersonalBestEvaluatorTest extends TestCase
{
    private PersonalBestProviderGateway&MockObject $personalBestProvider;
    private PersonalBestEvaluator $evaluator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->personalBestProvider = $this->createMock(PersonalBestProviderGateway::class);
        $this->evaluator = new PersonalBestEvaluator($this->personalBestProvider);
        $this->player = self::buildPlayer('player-1');
    }

    public function testItThrowsWhenWorkoutDateEndIsNotSet(): void
    {
        $workout = new WorkoutDataModel($this->player, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->id = 'workout-no-end';

        $this->expectException(\LogicException::class);

        $this->evaluator->evaluate($workout);
    }

    public function testItProducesEveryCategoryForAStrengthMovementWithoutExistingPBs(): void
    {
        $movement = self::buildMovement('bench-press', tracksRepetitions: true, tracksWeight: true);
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise, achievedReps: 10, achievedWeight: '50.00');
        self::attachSet($exercise, achievedReps: 8, achievedWeight: '60.00');

        $this->personalBestProvider->method('findOneForPlayerMovementType')->willReturn(null);

        $upserts = $this->evaluator->evaluate($workout);

        $byType = self::indexByType($upserts);
        self::assertSame('60.0000', $byType[PersonalBestTypeRegistry::HIGHEST_WEIGHT]->personalBest->value);
        self::assertSame('10.0000', $byType[PersonalBestTypeRegistry::HIGHEST_REPS]->personalBest->value);
        // Best one-set volume = max(10*50, 8*60) = max(500, 480) = 500
        self::assertSame('500.0000', $byType[PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET]->personalBest->value);
        // Workout volume = 10*50 + 8*60 = 980
        self::assertSame('980.0000', $byType[PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT]->personalBest->value);
        // VOLUME_WORKOUT has no source set
        self::assertNull($byType[PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT]->personalBest->exerciseSet);
        // Other categories have a source set
        self::assertNotNull($byType[PersonalBestTypeRegistry::HIGHEST_WEIGHT]->personalBest->exerciseSet);
        // Cardio fields aren't tracked, no upsert
        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_DURATION, $byType);
        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_DISTANCE, $byType);
        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_SPEED, $byType);
        // Every upsert is "new" since no existing PB
        foreach ($upserts as $upsert) {
            self::assertTrue($upsert->isNew);
        }
    }

    public function testItProducesCardioCategoriesForADistanceMovement(): void
    {
        $movement = self::buildMovement('running', tracksDuration: true, tracksDistance: true);
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise, achievedDurationSeconds: 1800, achievedDistanceMeters: '5000.00');
        self::attachSet($exercise, achievedDurationSeconds: 600, achievedDistanceMeters: '2000.00');

        $this->personalBestProvider->method('findOneForPlayerMovementType')->willReturn(null);

        $byType = self::indexByType($this->evaluator->evaluate($workout));

        self::assertSame('1800.0000', $byType[PersonalBestTypeRegistry::HIGHEST_DURATION]->personalBest->value);
        self::assertSame('5000.0000', $byType[PersonalBestTypeRegistry::HIGHEST_DISTANCE]->personalBest->value);
        // Speed: 5000/1800 ≈ 2.7777; 2000/600 ≈ 3.3333 — second set wins
        self::assertSame('3.3333', $byType[PersonalBestTypeRegistry::HIGHEST_SPEED]->personalBest->value);
        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_WEIGHT, $byType);
        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET, $byType);
    }

    public function testItSkipsSpeedComputationWhenDurationIsZero(): void
    {
        $movement = self::buildMovement('running', tracksDuration: true, tracksDistance: true);
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise, achievedDurationSeconds: 0, achievedDistanceMeters: '100.00');

        $this->personalBestProvider->method('findOneForPlayerMovementType')->willReturn(null);

        $byType = self::indexByType($this->evaluator->evaluate($workout));

        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_SPEED, $byType);
        self::assertSame('100.0000', $byType[PersonalBestTypeRegistry::HIGHEST_DISTANCE]->personalBest->value);
    }

    public function testItDoesNotProduceUpsertOnATieWithExistingPB(): void
    {
        $movement = self::buildMovement('squat', tracksRepetitions: true, tracksWeight: true);
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise, achievedReps: 5, achievedWeight: '100.00');

        $existingWeightPB = self::buildExistingPB($this->player, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT, '100.0000');
        $existingRepsPB = self::buildExistingPB($this->player, $movement, PersonalBestTypeRegistry::HIGHEST_REPS, '5');

        $this->personalBestProvider
            ->method('findOneForPlayerMovementType')
            ->willReturnCallback(static function (PlayerDataModel $player, MovementDataModel $movement, string $type) use ($existingWeightPB, $existingRepsPB) {
                return match ($type) {
                    PersonalBestTypeRegistry::HIGHEST_WEIGHT => $existingWeightPB,
                    PersonalBestTypeRegistry::HIGHEST_REPS => $existingRepsPB,
                    default => null,
                };
            });

        $byType = self::indexByType($this->evaluator->evaluate($workout));

        // Tie on weight + reps → no upsert. But VOLUME_ONE_SET (5 * 100 = 500) and VOLUME_WORKOUT
        // are still new (no existing PB returned for those types).
        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_WEIGHT, $byType);
        self::assertArrayNotHasKey(PersonalBestTypeRegistry::HIGHEST_REPS, $byType);
        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET, $byType);
        self::assertTrue($byType[PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET]->isNew);
    }

    public function testItProducesAnUpdateUpsertWhenBeatingAnExistingPB(): void
    {
        $movement = self::buildMovement('squat', tracksRepetitions: true, tracksWeight: true);
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise, achievedReps: 5, achievedWeight: '120.00');

        $existingWeightPB = self::buildExistingPB($this->player, $movement, PersonalBestTypeRegistry::HIGHEST_WEIGHT, '100.0000');

        $this->personalBestProvider
            ->method('findOneForPlayerMovementType')
            ->willReturnCallback(static function (PlayerDataModel $player, MovementDataModel $movement, string $type) use ($existingWeightPB) {
                return PersonalBestTypeRegistry::HIGHEST_WEIGHT === $type ? $existingWeightPB : null;
            });

        $byType = self::indexByType($this->evaluator->evaluate($workout));

        self::assertArrayHasKey(PersonalBestTypeRegistry::HIGHEST_WEIGHT, $byType);
        $upsert = $byType[PersonalBestTypeRegistry::HIGHEST_WEIGHT];
        self::assertFalse($upsert->isNew);
        self::assertSame($existingWeightPB, $upsert->personalBest);
        self::assertSame('120.0000', $upsert->personalBest->value);
        self::assertSame($workout, $upsert->personalBest->workout);
    }

    public function testItAggregatesAcrossExercisesThatShareTheSameMovement(): void
    {
        $movement = self::buildMovement('pull-up', tracksRepetitions: true);
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);
        $first = self::attachExercise($workout, $movement);
        $second = self::attachExercise($workout, $movement);
        self::attachSet($first, achievedReps: 8);
        self::attachSet($second, achievedReps: 12);

        $this->personalBestProvider->method('findOneForPlayerMovementType')->willReturn(null);

        $byType = self::indexByType($this->evaluator->evaluate($workout));

        self::assertSame('12.0000', $byType[PersonalBestTypeRegistry::HIGHEST_REPS]->personalBest->value);
    }

    /**
     * @param list<\App\Domain\Service\PersonalBestUpsert> $upserts
     *
     * @return array<string, \App\Domain\Service\PersonalBestUpsert>
     */
    private static function indexByType(array $upserts): array
    {
        $result = [];
        foreach ($upserts as $upsert) {
            $result[$upsert->personalBest->type] = $upsert;
        }

        return $result;
    }

    private static function buildPlayer(string $id): PlayerDataModel
    {
        $user = new UserDataModel($id.'@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';
        $player = new PlayerDataModel($user, 'Tester '.$id);
        $player->id = $id;

        return $player;
    }

    private static function buildMovement(
        string $slug,
        bool $tracksRepetitions = false,
        bool $tracksWeight = false,
        bool $tracksDuration = false,
        bool $tracksDistance = false,
    ): MovementDataModel {
        $movement = new MovementDataModel($slug, new MuscleDataModel('Chest'));
        $movement->id = 'movement-'.$slug;
        $movement->slug = $slug;
        $movement->tracksRepetitions = $tracksRepetitions;
        $movement->tracksWeight = $tracksWeight;
        $movement->tracksDuration = $tracksDuration;
        $movement->tracksDistance = $tracksDistance;

        return $movement;
    }

    private static function buildWorkout(PlayerDataModel $player, string $status, ?\DateTimeImmutable $dateEnd = null): WorkoutDataModel
    {
        $workout = new WorkoutDataModel($player, $status);
        $workout->id = 'workout-'.uniqid();
        $workout->dateEnd = $dateEnd ?? new \DateTimeImmutable('2026-04-30T12:00:00Z');

        return $workout;
    }

    private static function attachExercise(WorkoutDataModel $workout, MovementDataModel $movement): ExerciseDataModel
    {
        $exercise = new ExerciseDataModel($workout, $movement, $workout->exercises->count(), 60);
        $exercise->id = 'exercise-'.uniqid();
        $workout->exercises->add($exercise);

        return $exercise;
    }

    /**
     * @param numeric-string|null $achievedWeight
     * @param numeric-string|null $achievedDistanceMeters
     */
    private static function attachSet(
        ExerciseDataModel $exercise,
        ?int $achievedReps = null,
        ?string $achievedWeight = null,
        ?int $achievedDurationSeconds = null,
        ?string $achievedDistanceMeters = null,
    ): ExerciseSetDataModel {
        $set = new ExerciseSetDataModel($exercise, $exercise->exerciseSets->count());
        $set->id = 'set-'.uniqid().'-'.$exercise->exerciseSets->count();
        $set->achievedReps = $achievedReps;
        $set->achievedWeight = $achievedWeight;
        $set->achievedDurationSeconds = $achievedDurationSeconds;
        $set->achievedDistanceMeters = $achievedDistanceMeters;
        $set->completed = true;
        $exercise->exerciseSets->add($set);

        return $set;
    }

    /**
     * @param numeric-string $value
     */
    private static function buildExistingPB(PlayerDataModel $player, MovementDataModel $movement, string $type, string $value): PersonalBestDataModel
    {
        $pb = new PersonalBestDataModel($player, $movement, $type, $value, new \DateTimeImmutable('2026-01-01T12:00:00Z'));
        $pb->id = 'pb-'.uniqid();

        return $pb;
    }
}
