<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdateStepsDailyTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpdateStepsDailyTargetValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateStepsDailyTargetValidatorTest extends TestCase
{
    private UpdateStepsDailyTargetValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdateStepsDailyTargetValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveTarget(): void
    {
        $this->validator->validate(new UpdateStepsDailyTargetDataInput(8000));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new UpdateStepsDailyTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateStepsDailyTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('target', $e->violations);
        }
    }

    public function testItRejectsANegativeTarget(): void
    {
        try {
            $this->validator->validate(new UpdateStepsDailyTargetDataInput(-100));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateStepsDailyTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('target', $e->violations);
        }
    }
}
