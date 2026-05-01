<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetAchievedDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\ExerciseSet\UpdateExerciseSetAchievedValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateExerciseSetAchievedValidatorTest extends TestCase
{
    private UpdateExerciseSetAchievedValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new UpdateExerciseSetAchievedValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForValidValues(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true, 'tracksWeight' => true]);

        $this->validator->validate($this->player, new UpdateExerciseSetAchievedDataInput($set->id, achievedReps: 8, achievedWeight: '42.50'), $set);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $set = self::buildSet(self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true]);

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new UpdateExerciseSetAchievedDataInput($set->id), $set);
    }

    public function testItRejectsAPlannedWorkout(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::PLANNED), ['tracksRepetitions' => true]);

        try {
            $this->validator->validate($this->player, new UpdateExerciseSetAchievedDataInput($set->id), $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetAchievedValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
        }
    }

    public function testItRejectsTrackingFieldsThatTheMovementDoesNotTrack(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true]);

        try {
            $this->validator->validate($this->player, new UpdateExerciseSetAchievedDataInput($set->id, achievedReps: 5, achievedWeight: '50.00'), $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetAchievedValidator::TRACKING_MISMATCH_ERROR_CODE, $e->errorCode);
        }
    }

    public function testItRejectsNegativeAchievedDuration(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksDuration' => true]);

        try {
            $this->validator->validate($this->player, new UpdateExerciseSetAchievedDataInput($set->id, achievedDurationSeconds: -10), $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetAchievedValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('achievedDurationSeconds', $e->violations);
        }
    }

    public function testItRejectsMalformedDistance(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksDistance' => true]);

        $class = new \ReflectionClass(UpdateExerciseSetAchievedDataInput::class);
        $input = $class->newInstanceWithoutConstructor();
        $class->getProperty('exerciseSetId')->setValue($input, $set->id);
        $class->getProperty('achievedReps')->setValue($input, null);
        $class->getProperty('achievedWeight')->setValue($input, null);
        $class->getProperty('achievedDurationSeconds')->setValue($input, null);
        $class->getProperty('achievedDistanceMeters')->setValue($input, 'far');
        $class->getProperty('achievedInclinePercent')->setValue($input, null);
        $class->getProperty('achievedInclineMeters')->setValue($input, null);

        try {
            $this->validator->validate($this->player, $input, $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('achievedDistanceMeters', $e->violations);
        }
    }

    private static function buildPlayer(string $id): PlayerDataModel
    {
        $user = new UserDataModel($id.'@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';
        $player = new PlayerDataModel($user, 'Tester '.$id);
        $player->id = $id;

        return $player;
    }

    private static function buildWorkout(PlayerDataModel $owner, string $status): WorkoutDataModel
    {
        $workout = new WorkoutDataModel($owner, $status);
        $workout->id = 'workout-'.uniqid();

        return $workout;
    }

    /**
     * @param array{tracksRepetitions?: bool, tracksWeight?: bool, tracksDuration?: bool, tracksDistance?: bool, tracksInclinePercent?: bool, tracksInclineMeters?: bool} $tracks
     */
    private static function buildSet(WorkoutDataModel $workout, array $tracks = []): ExerciseSetDataModel
    {
        $movement = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $movement->slug = 'bench-press';
        $movement->tracksRepetitions = $tracks['tracksRepetitions'] ?? false;
        $movement->tracksWeight = $tracks['tracksWeight'] ?? false;
        $movement->tracksDuration = $tracks['tracksDuration'] ?? false;
        $movement->tracksDistance = $tracks['tracksDistance'] ?? false;
        $movement->tracksInclinePercent = $tracks['tracksInclinePercent'] ?? false;
        $movement->tracksInclineMeters = $tracks['tracksInclineMeters'] ?? false;

        $exercise = new ExerciseDataModel($workout, $movement, 0, 60);
        $exercise->id = 'exercise-'.uniqid();
        $set = new ExerciseSetDataModel($exercise, 0);
        $set->id = 'set-'.uniqid();

        return $set;
    }
}
