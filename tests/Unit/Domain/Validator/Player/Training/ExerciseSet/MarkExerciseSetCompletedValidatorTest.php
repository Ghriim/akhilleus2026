<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\MarkExerciseSetCompletedDataInput;
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
use App\Domain\Validator\Player\Training\ExerciseSet\MarkExerciseSetCompletedValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class MarkExerciseSetCompletedValidatorTest extends TestCase
{
    private MarkExerciseSetCompletedValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new MarkExerciseSetCompletedValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForAnInProgressWorkout(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS));

        $this->validator->validate($this->player, new MarkExerciseSetCompletedDataInput($set->id), $set);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $set = self::buildSet(self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS));

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new MarkExerciseSetCompletedDataInput($set->id), $set);
    }

    public function testItRejectsAPlannedWorkout(): void
    {
        $set = self::buildSet(self::buildWorkout($this->player, WorkoutStatusRegistry::PLANNED));

        try {
            $this->validator->validate($this->player, new MarkExerciseSetCompletedDataInput($set->id), $set);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(MarkExerciseSetCompletedValidator::ERROR_CODE, $e->errorCode);
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

    private static function buildSet(WorkoutDataModel $workout): ExerciseSetDataModel
    {
        $exercise = new ExerciseDataModel($workout, new MovementDataModel('Bench press', new MuscleDataModel('Chest')), 0, 60);
        $exercise->id = 'exercise-'.uniqid();
        $set = new ExerciseSetDataModel($exercise, 0);
        $set->id = 'set-'.uniqid();

        return $set;
    }
}
