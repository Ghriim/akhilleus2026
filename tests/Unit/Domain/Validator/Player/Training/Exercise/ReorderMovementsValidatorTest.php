<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\ReorderMovementsDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\ReorderMovementsValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ReorderMovementsValidatorTest extends TestCase
{
    private ReorderMovementsValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new ReorderMovementsValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForValidInput(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS);

        $this->validator->validate($this->player, new ReorderMovementsDataInput($workout->id, ['e-1', 'e-2']), $workout);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $workout = self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::IN_PROGRESS);

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new ReorderMovementsDataInput($workout->id, ['e-1']), $workout);
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);

        try {
            $this->validator->validate($this->player, new ReorderMovementsDataInput($workout->id, ['e-1']), $workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ReorderMovementsValidator::ILLEGAL_STATUS_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItRejectsAnEmptyOrderedExerciseIds(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS);

        try {
            $this->validator->validate($this->player, new ReorderMovementsDataInput($workout->id, []), $workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ReorderMovementsValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('orderedExerciseIds', $e->violations);
        }
    }

    public function testItRejectsDuplicateOrderedExerciseIds(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS);

        try {
            $this->validator->validate($this->player, new ReorderMovementsDataInput($workout->id, ['e-1', 'e-1']), $workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ReorderMovementsValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('orderedExerciseIds', $e->violations);
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
}
