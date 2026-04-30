<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartPlannedWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class StartPlannedWorkoutValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAValidInput(): void
    {
        $validator = new StartPlannedWorkoutValidator($this->createMock(LoggedPlayerResolverInterface::class));

        $validator->validate(new StartPlannedWorkoutDataInput('any-id'));

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $validator = new StartPlannedWorkoutValidator($this->createMock(LoggedPlayerResolverInterface::class));

        $this->expectException(\LogicException::class);

        $validator->validate(new class () implements DataInputInterface {});
    }
}
