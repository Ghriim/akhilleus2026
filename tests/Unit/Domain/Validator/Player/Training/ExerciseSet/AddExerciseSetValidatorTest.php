<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\AddExerciseSetDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\ExerciseSet\AddExerciseSetValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class AddExerciseSetValidatorTest extends TestCase
{
    private AddExerciseSetValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new AddExerciseSetValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForValidPlannedValuesMatchingTrackingFlags(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true, 'tracksWeight' => true]);

        $this->validator->validate($this->player, new AddExerciseSetDataInput(
            $exercise->id,
            plannedReps: 10,
            plannedWeight: '50.00',
        ), $exercise);

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesWhenAllPlannedValuesAreNull(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true]);

        $this->validator->validate($this->player, new AddExerciseSetDataInput($exercise->id), $exercise);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $exercise = self::buildExercise(self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true]);

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new AddExerciseSetDataInput($exercise->id), $exercise);
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED), ['tracksRepetitions' => true]);

        try {
            $this->validator->validate($this->player, new AddExerciseSetDataInput($exercise->id), $exercise);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddExerciseSetValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItRejectsTrackingFieldsThatTheMovementDoesNotTrack(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true]);

        try {
            $this->validator->validate($this->player, new AddExerciseSetDataInput(
                $exercise->id,
                plannedReps: 5,
                plannedWeight: '50.00',
            ), $exercise);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddExerciseSetValidator::TRACKING_MISMATCH_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('plannedWeight', $e->violations);
        }
    }

    public function testItRejectsNegativeNumericValues(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksRepetitions' => true, 'tracksDuration' => true]);

        try {
            $this->validator->validate($this->player, new AddExerciseSetDataInput(
                $exercise->id,
                plannedReps: -1,
                plannedDurationSeconds: -5,
            ), $exercise);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddExerciseSetValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('plannedReps', $e->violations);
            self::assertArrayHasKey('plannedDurationSeconds', $e->violations);
        }
    }

    public function testItRejectsNonNumericDecimalStrings(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS), ['tracksWeight' => true]);

        $class = new \ReflectionClass(AddExerciseSetDataInput::class);
        $input = $class->newInstanceWithoutConstructor();
        $class->getProperty('exerciseId')->setValue($input, $exercise->id);
        $class->getProperty('plannedReps')->setValue($input, null);
        $class->getProperty('plannedWeight')->setValue($input, 'fifty');
        $class->getProperty('plannedDurationSeconds')->setValue($input, null);
        $class->getProperty('plannedDistanceMeters')->setValue($input, null);
        $class->getProperty('plannedInclinePercent')->setValue($input, null);
        $class->getProperty('plannedInclineMeters')->setValue($input, null);

        try {
            $this->validator->validate($this->player, $input, $exercise);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('plannedWeight', $e->violations);
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
    private static function buildExercise(WorkoutDataModel $workout, array $tracks = []): ExerciseDataModel
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

        return $exercise;
    }
}
