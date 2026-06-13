<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\ListWeightForRangeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Player\Tracking\Weight\ListWeightForRangeValidator;
use PHPUnit\Framework\TestCase;

final class ListWeightForRangeValidatorTest extends TestCase
{
    private ListWeightForRangeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListWeightForRangeValidator();
    }

    public function testItPassesForAValidRange(): void
    {
        $this->validator->validate(new ListWeightForRangeDataInput(
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesForASingleDayRange(): void
    {
        $this->validator->validate(new ListWeightForRangeDataInput(
            new \DateTimeImmutable('2026-05-07'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAReversedRange(): void
    {
        try {
            $this->validator->validate(new ListWeightForRangeDataInput(
                new \DateTimeImmutable('2026-05-07'),
                new \DateTimeImmutable('2026-05-01'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListWeightForRangeValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('from', $e->violations);
        }
    }
}
