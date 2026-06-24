<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdatePlayerSleepTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Sleep\UpdatePlayerSleepTargetValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdatePlayerSleepTargetValidatorTest extends TestCase
{
    private UpdatePlayerSleepTargetValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdatePlayerSleepTargetValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveTarget(): void
    {
        $this->validator->validate(new UpdatePlayerSleepTargetDataInput(480));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerSleepTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerSleepTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMinutes', $e->violations);
        }
    }

    public function testItRejectsANegativeTarget(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerSleepTargetDataInput(-30));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerSleepTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMinutes', $e->violations);
        }
    }
}
