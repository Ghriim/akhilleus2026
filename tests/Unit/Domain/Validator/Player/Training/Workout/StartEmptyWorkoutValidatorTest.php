<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartEmptyWorkoutValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class StartEmptyWorkoutValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAValidInput(): void
    {
        $validator = new StartEmptyWorkoutValidator($this->createMock(LoggedPlayerResolverInterface::class));

        $validator->validate(new StartEmptyWorkoutDataInput());

        $this->expectNotToPerformAssertions();
    }
}
