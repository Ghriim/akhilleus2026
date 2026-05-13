<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpsertStepsForDayValidatorTest extends TestCase
{
    private UpsertStepsForDayValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpsertStepsForDayValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveCount(): void
    {
        $this->validator->validate(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-07'), 12000));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesForZero(): void
    {
        $this->validator->validate(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-07'), 0));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsANegativeCount(): void
    {
        try {
            $this->validator->validate(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-07'), -1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpsertStepsForDayValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Step count must be zero or positive.', $e->violations['count'] ?? []);
        }
    }
}
