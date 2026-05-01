<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutHistoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Player\Training\Workout\ListWorkoutHistoryValidator;
use PHPUnit\Framework\TestCase;

final class ListWorkoutHistoryValidatorTest extends TestCase
{
    private ListWorkoutHistoryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListWorkoutHistoryValidator();
    }

    public function testItPassesForDefaultInput(): void
    {
        $this->validator->validate(new ListWorkoutHistoryDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesAtTheUpperBoundOfPerPage(): void
    {
        $this->validator->validate(new ListWorkoutHistoryDataInput(1, ListWorkoutHistoryDataInput::MAX_PER_PAGE));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAPageBelowOne(): void
    {
        try {
            $this->validator->validate(new ListWorkoutHistoryDataInput(0, 20));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListWorkoutHistoryValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('page', $e->violations);
        }
    }

    public function testItRejectsAPerPageBelowOne(): void
    {
        try {
            $this->validator->validate(new ListWorkoutHistoryDataInput(1, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('perPage', $e->violations);
        }
    }

    public function testItRejectsAPerPageAboveTheMax(): void
    {
        try {
            $this->validator->validate(new ListWorkoutHistoryDataInput(1, ListWorkoutHistoryDataInput::MAX_PER_PAGE + 1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('perPage', $e->violations);
        }
    }

    public function testItAccumulatesPageAndPerPageViolations(): void
    {
        try {
            $this->validator->validate(new ListWorkoutHistoryDataInput(0, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('page', $e->violations);
            self::assertArrayHasKey('perPage', $e->violations);
        }
    }
}
