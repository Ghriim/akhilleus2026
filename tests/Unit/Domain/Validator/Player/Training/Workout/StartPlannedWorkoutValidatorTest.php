<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartPlannedWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class StartPlannedWorkoutValidatorTest extends TestCase
{
    private StartPlannedWorkoutValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->validator = new StartPlannedWorkoutValidator($this->createMock(LoggedPlayerResolverInterface::class));
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesForAPlannedWorkoutOwnedByThePlayer(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::PLANNED);

        $this->validator->validate($this->player, new StartPlannedWorkoutDataInput($workout->id), $workout);

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsUnauthorizedWhenWorkoutBelongsToAnotherPlayer(): void
    {
        $workout = self::buildWorkout(self::buildPlayer('player-2'), WorkoutStatusRegistry::PLANNED);

        $this->expectException(UnauthorizedException::class);

        $this->validator->validate($this->player, new StartPlannedWorkoutDataInput($workout->id), $workout);
    }

    public function testItRejectsAnInProgressWorkout(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::IN_PROGRESS);

        try {
            $this->validator->validate($this->player, new StartPlannedWorkoutDataInput($workout->id), $workout);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(StartPlannedWorkoutValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItRejectsACompletedWorkout(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::COMPLETED);

        $this->expectException(ValidationException::class);

        $this->validator->validate($this->player, new StartPlannedWorkoutDataInput($workout->id), $workout);
    }

    public function testItRejectsACanceledWorkout(): void
    {
        $workout = self::buildWorkout($this->player, WorkoutStatusRegistry::CANCELED);

        $this->expectException(ValidationException::class);

        $this->validator->validate($this->player, new StartPlannedWorkoutDataInput($workout->id), $workout);
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
