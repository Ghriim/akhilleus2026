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
use App\Domain\Service\ExerciseSetCompletionEvaluator;
use PHPUnit\Framework\TestCase;

final class ExerciseSetCompletionEvaluatorTest extends TestCase
{
    public function testReturnsTrueWhenAllTrackedAchievedFieldsAreSet(): void
    {
        $movement = self::buildMovement(['tracksRepetitions' => true, 'tracksWeight' => true]);
        $set = self::buildSet($movement);
        $set->achievedReps = 10;
        $set->achievedWeight = '50.00';

        self::assertTrue(ExerciseSetCompletionEvaluator::isComplete($set, $movement));
    }

    public function testReturnsTrueWhenMovementTracksNothing(): void
    {
        $movement = self::buildMovement([]);
        $set = self::buildSet($movement);

        self::assertTrue(ExerciseSetCompletionEvaluator::isComplete($set, $movement));
    }

    public function testReturnsFalseWhenATrackedFieldIsMissing(): void
    {
        $movement = self::buildMovement(['tracksRepetitions' => true, 'tracksWeight' => true]);
        $set = self::buildSet($movement);
        $set->achievedReps = 10;
        // achievedWeight stays null → set is not complete.

        self::assertFalse(ExerciseSetCompletionEvaluator::isComplete($set, $movement));
    }

    public function testIgnoresAchievedValuesForUntrackedDimensions(): void
    {
        $movement = self::buildMovement(['tracksRepetitions' => true]);
        $set = self::buildSet($movement);
        $set->achievedReps = 10;
        // achievedWeight is set but the movement does not track it — completion should ignore it.
        $set->achievedWeight = '50.00';

        self::assertTrue(ExerciseSetCompletionEvaluator::isComplete($set, $movement));
    }

    public function testCoversEveryTrackingDimensionIndependently(): void
    {
        // Each tracked dimension on its own is sufficient to gate completion.
        foreach (
            [
                ['tracksDuration' => true],
                ['tracksDistance' => true],
                ['tracksInclinePercent' => true],
                ['tracksInclineMeters' => true],
            ] as $tracks
        ) {
            $movement = self::buildMovement($tracks);
            $set = self::buildSet($movement);

            self::assertFalse(ExerciseSetCompletionEvaluator::isComplete($set, $movement));
        }
    }

    public function testCompletesAllSixDimensionsAtOnce(): void
    {
        $movement = self::buildMovement([
            'tracksRepetitions' => true,
            'tracksWeight' => true,
            'tracksDuration' => true,
            'tracksDistance' => true,
            'tracksInclinePercent' => true,
            'tracksInclineMeters' => true,
        ]);
        $set = self::buildSet($movement);
        $set->achievedReps = 10;
        $set->achievedWeight = '50.00';
        $set->achievedDurationSeconds = 60;
        $set->achievedDistanceMeters = '100.00';
        $set->achievedInclinePercent = '5.00';
        $set->achievedInclineMeters = '5.00';

        self::assertTrue(ExerciseSetCompletionEvaluator::isComplete($set, $movement));
    }

    /**
     * @param array{tracksRepetitions?: bool, tracksWeight?: bool, tracksDuration?: bool, tracksDistance?: bool, tracksInclinePercent?: bool, tracksInclineMeters?: bool} $tracks
     */
    private static function buildMovement(array $tracks): MovementDataModel
    {
        $movement = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $movement->slug = 'bench-press';
        $movement->tracksRepetitions = $tracks['tracksRepetitions'] ?? false;
        $movement->tracksWeight = $tracks['tracksWeight'] ?? false;
        $movement->tracksDuration = $tracks['tracksDuration'] ?? false;
        $movement->tracksDistance = $tracks['tracksDistance'] ?? false;
        $movement->tracksInclinePercent = $tracks['tracksInclinePercent'] ?? false;
        $movement->tracksInclineMeters = $tracks['tracksInclineMeters'] ?? false;

        return $movement;
    }

    private static function buildSet(MovementDataModel $movement): ExerciseSetDataModel
    {
        $user = new UserDataModel('player@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';
        $player = new PlayerDataModel($user, 'Tester');
        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $exercise = new ExerciseDataModel($workout, $movement, 0, 60);

        return new ExerciseSetDataModel($exercise, 0);
    }
}
