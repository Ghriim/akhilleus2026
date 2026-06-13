<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationDailyTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\UpdateHydrationDailyTargetValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateHydrationDailyTargetValidatorTest extends TestCase
{
    private UpdateHydrationDailyTargetValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdateHydrationDailyTargetValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveTarget(): void
    {
        $this->validator->validate(new UpdateHydrationDailyTargetDataInput(2500));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new UpdateHydrationDailyTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateHydrationDailyTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMl', $e->violations);
        }
    }

    public function testItRejectsANegativeTarget(): void
    {
        try {
            $this->validator->validate(new UpdateHydrationDailyTargetDataInput(-100));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateHydrationDailyTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMl', $e->violations);
        }
    }
}
