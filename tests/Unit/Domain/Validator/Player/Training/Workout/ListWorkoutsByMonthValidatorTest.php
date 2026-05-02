<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutsByMonthDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Player\Training\Workout\ListWorkoutsByMonthValidator;
use PHPUnit\Framework\TestCase;

final class ListWorkoutsByMonthValidatorTest extends TestCase
{
    private ListWorkoutsByMonthValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListWorkoutsByMonthValidator();
    }

    public function testItPassesForCurrentMonth(): void
    {
        $this->validator->validate(new ListWorkoutsByMonthDataInput(2026, 5));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesAtTheLowerYearBound(): void
    {
        $this->validator->validate(new ListWorkoutsByMonthDataInput(ListWorkoutsByMonthDataInput::MIN_YEAR, 1));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesAtTheUpperYearBound(): void
    {
        $this->validator->validate(new ListWorkoutsByMonthDataInput(ListWorkoutsByMonthDataInput::MAX_YEAR, 12));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAYearBelowMin(): void
    {
        try {
            $this->validator->validate(new ListWorkoutsByMonthDataInput(ListWorkoutsByMonthDataInput::MIN_YEAR - 1, 1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListWorkoutsByMonthValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('year', $e->violations);
        }
    }

    public function testItRejectsAYearAboveMax(): void
    {
        try {
            $this->validator->validate(new ListWorkoutsByMonthDataInput(ListWorkoutsByMonthDataInput::MAX_YEAR + 1, 1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('year', $e->violations);
        }
    }

    public function testItRejectsAMonthBelowOne(): void
    {
        try {
            $this->validator->validate(new ListWorkoutsByMonthDataInput(2026, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('month', $e->violations);
        }
    }

    public function testItRejectsAMonthAboveTwelve(): void
    {
        try {
            $this->validator->validate(new ListWorkoutsByMonthDataInput(2026, 13));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('month', $e->violations);
        }
    }

    public function testItAccumulatesYearAndMonthViolations(): void
    {
        try {
            $this->validator->validate(new ListWorkoutsByMonthDataInput(0, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('year', $e->violations);
            self::assertArrayHasKey('month', $e->violations);
        }
    }
}
