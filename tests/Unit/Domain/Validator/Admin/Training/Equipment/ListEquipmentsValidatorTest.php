<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\ListEquipmentsDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Admin\Training\Equipment\ListEquipmentsValidator;
use PHPUnit\Framework\TestCase;

final class ListEquipmentsValidatorTest extends TestCase
{
    private ListEquipmentsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListEquipmentsValidator();
    }

    public function testItPassesForDefaultInput(): void
    {
        $this->validator->validate(new ListEquipmentsDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesForLabelDescending(): void
    {
        $this->validator->validate(new ListEquipmentsDataInput('label', 'DESC'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnUnknownSortField(): void
    {
        try {
            $this->validator->validate(new ListEquipmentsDataInput('id', 'ASC'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListEquipmentsValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('sort', $e->violations);
            self::assertContains('Sort must be one of: label.', $e->violations['sort']);
        }
    }

    public function testItRejectsAnUnknownDirection(): void
    {
        try {
            $this->validator->validate(new ListEquipmentsDataInput('label', 'SIDEWAYS'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('direction', $e->violations);
            self::assertContains('Direction must be one of: ASC, DESC.', $e->violations['direction']);
        }
    }

    public function testItAccumulatesSortAndDirectionViolations(): void
    {
        try {
            $this->validator->validate(new ListEquipmentsDataInput('id', 'SIDEWAYS'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('sort', $e->violations);
            self::assertArrayHasKey('direction', $e->violations);
        }
    }
}
