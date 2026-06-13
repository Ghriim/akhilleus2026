<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdatePlayerDailyStepsTargetValidatorTest extends TestCase
{
    private UpdatePlayerDailyStepsTargetValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdatePlayerDailyStepsTargetValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveTarget(): void
    {
        $this->validator->validate(new UpdatePlayerDailyStepsTargetDataInput(7500));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerDailyStepsTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerDailyStepsTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('target', $e->violations);
        }
    }

    public function testItRejectsANegativeTarget(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerDailyStepsTargetDataInput(-1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerDailyStepsTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('target', $e->violations);
        }
    }
}
