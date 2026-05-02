<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Service\WorkoutAggregateEvaluator;
use PHPUnit\Framework\TestCase;

final class WorkoutAggregateEvaluatorTest extends TestCase
{
    public function testEvaluateMutatesAllFourAggregatesAndReturnsTheSameWorkout(): void
    {
        $workout = self::buildWorkout();
        $workout->dateStart = new \DateTimeImmutable('2026-05-01T10:00:00');
        $workout->dateEnd = new \DateTimeImmutable('2026-05-01T11:30:00');

        $movement = self::buildMovement();
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise, achievedWeight: '50.00', achievedDistance: '100.00', achievedIncline: '5.00');
        self::attachSet($exercise, achievedWeight: '60.00', achievedDistance: '200.00', achievedIncline: '10.00');

        $returned = WorkoutAggregateEvaluator::evaluate($workout);

        self::assertSame($workout, $returned);
        self::assertSame(5400, $workout->duration); // 1h30 = 5400s
        self::assertSame('110.00', $workout->volume);
        self::assertSame('300.00', $workout->distance);
        self::assertSame('15.00', $workout->inclineMeters);
    }

    public function testAggregatesAreNullWhenNoSetCarriesAValue(): void
    {
        $workout = self::buildWorkout();
        $workout->dateStart = new \DateTimeImmutable('2026-05-01T10:00:00');
        $workout->dateEnd = new \DateTimeImmutable('2026-05-01T11:00:00');

        $movement = self::buildMovement();
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise); // all achieved* null

        WorkoutAggregateEvaluator::evaluate($workout);

        self::assertSame(3600, $workout->duration);
        self::assertNull($workout->volume);
        self::assertNull($workout->distance);
        self::assertNull($workout->inclineMeters);
    }

    public function testAggregatesIgnoreNullSetsAndSumOnlyTheNonNullOnes(): void
    {
        $workout = self::buildWorkout();
        $workout->dateStart = new \DateTimeImmutable('2026-05-01T10:00:00');
        $workout->dateEnd = new \DateTimeImmutable('2026-05-01T10:10:00');

        $movement = self::buildMovement();
        $exercise = self::attachExercise($workout, $movement);
        self::attachSet($exercise, achievedWeight: '50.00');
        self::attachSet($exercise); // all null
        self::attachSet($exercise, achievedWeight: '70.00');

        WorkoutAggregateEvaluator::evaluate($workout);

        self::assertSame('120.00', $workout->volume);
        self::assertNull($workout->distance);
    }

    public function testAggregatesSpanMultipleExercises(): void
    {
        $workout = self::buildWorkout();
        $workout->dateStart = new \DateTimeImmutable('2026-05-01T10:00:00');
        $workout->dateEnd = new \DateTimeImmutable('2026-05-01T10:30:00');

        $movement = self::buildMovement();
        $exercise1 = self::attachExercise($workout, $movement);
        $exercise2 = self::attachExercise($workout, $movement);
        self::attachSet($exercise1, achievedWeight: '40.00');
        self::attachSet($exercise2, achievedWeight: '60.00');

        WorkoutAggregateEvaluator::evaluate($workout);

        self::assertSame('100.00', $workout->volume);
    }

    public function testDurationIsNullWhenStartOrEndIsMissing(): void
    {
        $workout = self::buildWorkout();
        // dateStart is null — duration must stay null.

        WorkoutAggregateEvaluator::evaluate($workout);

        self::assertNull($workout->duration);
    }

    private static function buildWorkout(): WorkoutDataModel
    {
        $user = new UserDataModel('player@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';
        $player = new PlayerDataModel($user, 'Tester');

        return new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
    }

    private static function buildMovement(): MovementDataModel
    {
        $movement = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $movement->slug = 'bench-press';

        return $movement;
    }

    private static function attachExercise(WorkoutDataModel $workout, MovementDataModel $movement): ExerciseDataModel
    {
        $exercise = new ExerciseDataModel($workout, $movement, $workout->exercises->count(), 60);
        $workout->exercises->add($exercise);

        return $exercise;
    }

    /**
     * @param numeric-string|null $achievedWeight
     * @param numeric-string|null $achievedDistance
     * @param numeric-string|null $achievedIncline
     */
    private static function attachSet(
        ExerciseDataModel $exercise,
        ?string $achievedWeight = null,
        ?string $achievedDistance = null,
        ?string $achievedIncline = null,
    ): ExerciseSetDataModel {
        $set = new ExerciseSetDataModel($exercise, $exercise->exerciseSets->count());
        $set->achievedWeight = $achievedWeight;
        $set->achievedDistanceMeters = $achievedDistance;
        $set->achievedInclineMeters = $achievedIncline;
        $exercise->exerciseSets->add($set);

        return $set;
    }
}
