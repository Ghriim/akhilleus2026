<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\UpdateLevelingConfigDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Leveling\LevelingConfig\UpdateLevelingConfigValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateLevelingConfigValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private UpdateLevelingConfigValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->validator = new UpdateLevelingConfigValidator($this->loggedUserResolver);
    }

    public function testItPassesAtTheMinimum(): void
    {
        $this->validator->validate(new UpdateLevelingConfigDataInput(UpdateLevelingConfigValidator::MIN_XP_PER_WORKOUT_MINUTE));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesAboveTheMinimum(): void
    {
        $this->validator->validate(new UpdateLevelingConfigDataInput(200));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAValueJustBelowTheMinimum(): void
    {
        $this->assertRejected(UpdateLevelingConfigValidator::MIN_XP_PER_WORKOUT_MINUTE - 1);
    }

    public function testItRejectsZero(): void
    {
        $this->assertRejected(0);
    }

    public function testItRejectsANegativeValue(): void
    {
        $this->assertRejected(-10);
    }

    private function assertRejected(int $xpPerWorkoutMinute): void
    {
        try {
            $this->validator->validate(new UpdateLevelingConfigDataInput($xpPerWorkoutMinute));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateLevelingConfigValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('xpPerWorkoutMinute', $e->violations);
        }
    }
}
