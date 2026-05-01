<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\AddMovementToWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\AddMovementToWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class AddMovementToWorkoutValidatorTest extends TestCase
{
    private AddMovementToWorkoutValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new AddMovementToWorkoutValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = new PlayerDataModel(self::buildUser(), 'Tester');
        $this->player->id = 'player-1';
    }

    public function testItPassesForAValidInputAndOwnedEditableWorkout(): void
    {
        $workout = $this->buildWorkout(WorkoutStatusRegistry::IN_PROGRESS);

        $this->validator->validate(
            $this->player,
            new AddMovementToWorkoutDataInput($workout->id, 'movement-1', 60),
            $workout,
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $other = new PlayerDataModel(self::buildUser(), 'Other');
        $other->id = 'player-2';
        $workout = new WorkoutDataModel($other, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->id = 'workout-x';

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate(
            $this->player,
            new AddMovementToWorkoutDataInput($workout->id, 'movement-1', 60),
            $workout,
        );
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $workout = $this->buildWorkout(WorkoutStatusRegistry::COMPLETED);

        try {
            $this->validator->validate(
                $this->player,
                new AddMovementToWorkoutDataInput($workout->id, 'movement-1', 60),
                $workout,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddMovementToWorkoutValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItRejectsAnEmptyWorkoutId(): void
    {
        $workout = $this->buildWorkout(WorkoutStatusRegistry::IN_PROGRESS);

        try {
            $this->validator->validate(
                $this->player,
                new AddMovementToWorkoutDataInput('  ', 'movement-1', 0),
                $workout,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddMovementToWorkoutValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('workoutId', $e->violations);
        }
    }

    public function testItRejectsAnEmptyMovementId(): void
    {
        $workout = $this->buildWorkout(WorkoutStatusRegistry::IN_PROGRESS);

        try {
            $this->validator->validate(
                $this->player,
                new AddMovementToWorkoutDataInput($workout->id, '', 0),
                $workout,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('movementId', $e->violations);
        }
    }

    public function testItRejectsANegativeRestDuration(): void
    {
        $workout = $this->buildWorkout(WorkoutStatusRegistry::IN_PROGRESS);

        try {
            $this->validator->validate(
                $this->player,
                new AddMovementToWorkoutDataInput($workout->id, 'movement-1', -1),
                $workout,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('restDurationSeconds', $e->violations);
        }
    }

    public function testItAccumulatesEveryInputViolation(): void
    {
        $workout = $this->buildWorkout(WorkoutStatusRegistry::IN_PROGRESS);

        try {
            $this->validator->validate(
                $this->player,
                new AddMovementToWorkoutDataInput('', '', -5),
                $workout,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('workoutId', $e->violations);
            self::assertArrayHasKey('movementId', $e->violations);
            self::assertArrayHasKey('restDurationSeconds', $e->violations);
        }
    }

    private function buildWorkout(string $status): WorkoutDataModel
    {
        $workout = new WorkoutDataModel($this->player, $status);
        $workout->id = 'workout-1';

        return $workout;
    }

    private static function buildUser(): UserDataModel
    {
        $user = new UserDataModel('test@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';

        return $user;
    }
}
