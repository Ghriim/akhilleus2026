<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartEmptyWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class StartEmptyWorkoutValidatorTest extends TestCase
{
    private WorkoutProviderGateway&MockObject $workoutProvider;
    private StartEmptyWorkoutValidator $validator;
    private PlayerDataModel $player;

    protected function setUp(): void
    {
        $this->workoutProvider = $this->createMock(WorkoutProviderGateway::class);
        $this->validator = new StartEmptyWorkoutValidator(
            $this->createMock(LoggedPlayerResolverInterface::class),
            $this->workoutProvider,
        );
        $this->player = self::buildPlayer('player-1');
    }

    public function testItPassesWhenNoWorkoutIsInProgress(): void
    {
        $this->workoutProvider->method('findInProgressByPlayer')->willReturn(null);

        $this->validator->validate($this->player, new StartEmptyWorkoutDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsWhenAnotherWorkoutIsAlreadyInProgress(): void
    {
        $existing = new WorkoutDataModel($this->player, WorkoutStatusRegistry::IN_PROGRESS);
        $existing->id = 'workout-existing';
        $this->workoutProvider->method('findInProgressByPlayer')->willReturn($existing);

        try {
            $this->validator->validate($this->player, new StartEmptyWorkoutDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(StartEmptyWorkoutValidator::ALREADY_IN_PROGRESS_CODE, $e->errorCode);
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
}
