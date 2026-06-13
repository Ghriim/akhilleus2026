<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdatePlayerDailyHydrationTargetValidatorTest extends TestCase
{
    private UpdatePlayerDailyHydrationTargetValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdatePlayerDailyHydrationTargetValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveTarget(): void
    {
        $this->validator->validate(new UpdatePlayerDailyHydrationTargetDataInput(2000));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerDailyHydrationTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerDailyHydrationTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMl', $e->violations);
        }
    }

    public function testItRejectsANegativeTarget(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerDailyHydrationTargetDataInput(-1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerDailyHydrationTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMl', $e->violations);
        }
    }
}
