<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\ListStepsForRangeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Player\Tracking\Steps\ListStepsForRangeValidator;
use PHPUnit\Framework\TestCase;

final class ListStepsForRangeValidatorTest extends TestCase
{
    private ListStepsForRangeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListStepsForRangeValidator();
    }

    public function testItPassesForAValidRange(): void
    {
        $this->validator->validate(new ListStepsForRangeDataInput(
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesForASingleDayRange(): void
    {
        $this->validator->validate(new ListStepsForRangeDataInput(
            new \DateTimeImmutable('2026-05-07'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAReversedRange(): void
    {
        try {
            $this->validator->validate(new ListStepsForRangeDataInput(
                new \DateTimeImmutable('2026-05-07'),
                new \DateTimeImmutable('2026-05-01'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListStepsForRangeValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('from', $e->violations);
        }
    }
}
