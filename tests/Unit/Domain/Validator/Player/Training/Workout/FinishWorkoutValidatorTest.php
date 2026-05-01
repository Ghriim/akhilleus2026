<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
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
use App\Domain\Validator\Player\Training\Workout\FinishWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class FinishWorkoutValidatorTest extends TestCase
{
    private FinishWorkoutValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new FinishWorkoutValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForAnInProgressWorkoutWithAllCompletedSets(): void
    {
        $workout = self::buildWorkoutWithSets($this->player, WorkoutStatusRegistry::IN_PROGRESS, [true, true]);

        $this->validator->validate($this->player, new FinishWorkoutDataInput($workout->id), $workout);

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesForAWorkoutWithoutAnySet(): void
    {
        $workout = self::buildWorkoutWithSets($this->player, WorkoutStatusRegistry::IN_PROGRESS, []);

        $this->validator->validate($this->player, new FinishWorkoutDataInput($workout->id), $workout);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $workout = self::buildWorkoutWithSets(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS, [true]);

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new FinishWorkoutDataInput($workout->id), $workout);
    }

    public function testItRejectsAPlannedWorkout(): void
    {
        $workout = self::buildWorkoutWithSets($this->player, WorkoutStatusRegistry::PLANNED, []);

        try {
            $this->validator->validate($this->player, new FinishWorkoutDataInput($workout->id), $workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(FinishWorkoutValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $workout = self::buildWorkoutWithSets($this->player, WorkoutStatusRegistry::COMPLETED, [true]);

        try {
            $this->validator->validate($this->player, new FinishWorkoutDataInput($workout->id), $workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(FinishWorkoutValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
        }
    }

    public function testItRejectsAWorkoutWithIncompleteSetsAndReturnsTheirIds(): void
    {
        $workout = self::buildWorkoutWithSets($this->player, WorkoutStatusRegistry::IN_PROGRESS, [true, false, false]);

        try {
            $this->validator->validate($this->player, new FinishWorkoutDataInput($workout->id), $workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(FinishWorkoutValidator::INCOMPLETE_SETS_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('exerciseSets', $e->violations);
            self::assertCount(2, $e->violations['exerciseSets']);
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

    /**
     * @param list<bool> $completedFlags
     */
    private static function buildWorkoutWithSets(PlayerDataModel $owner, string $status, array $completedFlags): WorkoutDataModel
    {
        $workout = new WorkoutDataModel($owner, $status);
        $workout->id = 'workout-'.uniqid();
        $movement = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $movement->slug = 'bench-press';
        $exercise = new ExerciseDataModel($workout, $movement, 0, 60);
        $exercise->id = 'exercise-'.uniqid();
        $workout->exercises->add($exercise);
        foreach ($completedFlags as $position => $completed) {
            $set = new ExerciseSetDataModel($exercise, $position);
            $set->id = 'set-'.uniqid().'-'.$position;
            $set->completed = $completed;
            $exercise->exerciseSets->add($set);
        }

        return $workout;
    }
}
