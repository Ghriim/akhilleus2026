<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\ListMusclesDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\Admin\Training\Muscle\ListMusclesValidator;
use PHPUnit\Framework\TestCase;

final class ListMusclesValidatorTest extends TestCase
{
    private ListMusclesValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ListMusclesValidator();
    }

    public function testItPassesForDefaultInput(): void
    {
        $this->validator->validate(new ListMusclesDataInput());

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnUnknownSortField(): void
    {
        try {
            $this->validator->validate(new ListMusclesDataInput('slug'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListMusclesValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('sort', $e->violations);
        }
    }

    public function testItRejectsAnUnknownDirection(): void
    {
        try {
            $this->validator->validate(new ListMusclesDataInput('label', 'random'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('direction', $e->violations);
        }
    }
}
