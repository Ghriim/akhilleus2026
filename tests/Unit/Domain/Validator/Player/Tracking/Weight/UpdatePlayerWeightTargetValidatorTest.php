<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdatePlayerWeightTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\UpdatePlayerWeightTargetValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdatePlayerWeightTargetValidatorTest extends TestCase
{
    private UpdatePlayerWeightTargetValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdatePlayerWeightTargetValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveTarget(): void
    {
        $this->validator->validate(new UpdatePlayerWeightTargetDataInput(75000));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerWeightTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerWeightTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetGrams', $e->violations);
        }
    }

    public function testItRejectsANegativeTarget(): void
    {
        try {
            $this->validator->validate(new UpdatePlayerWeightTargetDataInput(-1000));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerWeightTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetGrams', $e->violations);
        }
    }
}
