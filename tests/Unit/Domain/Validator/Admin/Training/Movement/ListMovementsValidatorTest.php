<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\ListMovementsDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Admin\Training\Movement\ListMovementsValidator;
use PHPUnit\Framework\TestCase;

final class ListMovementsValidatorTest extends TestCase
{
    private ListMovementsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListMovementsValidator();
    }

    public function testItPassesForDefaultInput(): void
    {
        $this->validator->validate(new ListMovementsDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnUnknownSortField(): void
    {
        try {
            $this->validator->validate(new ListMovementsDataInput('mainMuscleSlug'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListMovementsValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('sort', $e->violations);
        }
    }

    public function testItRejectsAnUnknownDirection(): void
    {
        try {
            $this->validator->validate(new ListMovementsDataInput('label', 'whatever'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('direction', $e->violations);
        }
    }
}
