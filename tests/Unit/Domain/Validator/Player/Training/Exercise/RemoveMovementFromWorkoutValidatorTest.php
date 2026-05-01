<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\RemoveMovementFromWorkoutDataInput;
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
use App\Domain\Validator\Player\Training\Exercise\RemoveMovementFromWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class RemoveMovementFromWorkoutValidatorTest extends TestCase
{
    private RemoveMovementFromWorkoutValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new RemoveMovementFromWorkoutValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForAnInProgressWorkout(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS));

        $this->validator->validate($this->player, new RemoveMovementFromWorkoutDataInput($exercise->id), $exercise);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $exercise = self::buildExercise(self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS));

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new RemoveMovementFromWorkoutDataInput($exercise->id), $exercise);
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $exercise = self::buildExercise(self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED));

        try {
            $this->validator->validate($this->player, new RemoveMovementFromWorkoutDataInput($exercise->id), $exercise);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(RemoveMovementFromWorkoutValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
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
