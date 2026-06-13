<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\ListSleepForRangeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Player\Tracking\Sleep\ListSleepForRangeValidator;
use PHPUnit\Framework\TestCase;

final class ListSleepForRangeValidatorTest extends TestCase
{
    private ListSleepForRangeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListSleepForRangeValidator();
    }

    public function testItPassesForAValidRange(): void
    {
        $this->validator->validate(new ListSleepForRangeDataInput(
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesForASingleDayRange(): void
    {
        $this->validator->validate(new ListSleepForRangeDataInput(
            new \DateTimeImmutable('2026-05-07'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAReversedRange(): void
    {
        try {
            $this->validator->validate(new ListSleepForRangeDataInput(
                new \DateTimeImmutable('2026-05-07'),
                new \DateTimeImmutable('2026-05-01'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListSleepForRangeValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('from', $e->violations);
        }
    }
}
