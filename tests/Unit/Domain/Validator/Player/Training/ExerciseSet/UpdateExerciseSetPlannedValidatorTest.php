<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetPlannedDataInput;
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
use App\Domain\Validator\Player\Training\ExerciseSet\UpdateExerciseSetPlannedValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateExerciseSetPlannedValidatorTest extends TestCase
{
    private UpdateExerciseSetPlannedValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new UpdateExerciseSetPlannedValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForValidPlannedValues(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true, 'tracksWeight' => true]);

        $this->validator->validate($this->player, new UpdateExerciseSetPlannedDataInput($set->id, plannedReps: 8, plannedWeight: '42.50'), $set);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $set = self::buildSet(self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true]);

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new UpdateExerciseSetPlannedDataInput($set->id), $set);
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED), ['tracksRepetitions' => true]);

        try {
            $this->validator->validate($this->player, new UpdateExerciseSetPlannedDataInput($set->id), $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetPlannedValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
        }
    }

    public function testItRejectsTrackingFieldsThatTheMovementDoesNotTrack(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true]);

        try {
            $this->validator->validate($this->player, new UpdateExerciseSetPlannedDataInput($set->id, plannedReps: 5, plannedWeight: '50.00'), $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetPlannedValidator::TRACKING_MISMATCH_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('plannedWeight', $e->violations);
        }
    }

    public function testItRejectsNegativeNumericValues(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true, 'tracksDuration' => true]);

        try {
            $this->validator->validate($this->player, new UpdateExerciseSetPlannedDataInput($set->id, plannedReps: -1, plannedDurationSeconds: -10), $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateExerciseSetPlannedValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('plannedReps', $e->violations);
            self::assertArrayHasKey('plannedDurationSeconds', $e->violations);
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
