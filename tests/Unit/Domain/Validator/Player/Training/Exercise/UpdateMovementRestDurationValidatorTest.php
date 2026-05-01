<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\UpdateMovementRestDurationDataInput;
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
use App\Domain\Validator\Player\Training\Exercise\UpdateMovementRestDurationValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateMovementRestDurationValidatorTest extends TestCase
{
    private UpdateMovementRestDurationValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new UpdateMovementRestDurationValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForValidInput(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS));

        $this->validator->validate($this->player, new UpdateMovementRestDurationDataInput($exercise->id, 90), $exercise);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $exercise = self::buildExercise(self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS));

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new UpdateMovementRestDurationDataInput($exercise->id, 60), $exercise);
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED));

        try {
            $this->validator->validate($this->player, new UpdateMovementRestDurationDataInput($exercise->id, 60), $exercise);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateMovementRestDurationValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItRejectsANegativeRestDuration(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS));

        try {
            $this->validator->validate($this->player, new UpdateMovementRestDurationDataInput($exercise->id, -1), $exercise);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateMovementRestDurationValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('restDurationSeconds', $e->violations);
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

    private static function buildExercise(WorkoutDataModel $workout): ExerciseDataModel
    {
        $movement = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $exercise = new ExerciseDataModel($workout, $movement, 0, 60);
        $exercise->id = 'exercise-'.uniqid();

        return $exercise;
    }
}
