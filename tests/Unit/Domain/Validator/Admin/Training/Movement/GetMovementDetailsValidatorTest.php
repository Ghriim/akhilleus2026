<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\GetMovementDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Validator\Admin\Training\Movement\GetMovementDetailsValidator;
use PHPUnit\Framework\TestCase;

final class GetMovementDetailsValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAValidInput(): void
    {
        (new GetMovementDetailsValidator())->validate(new GetMovementDetailsDataInput('any-id'));

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $this->expectException(\LogicException::class);

        (new GetMovementDetailsValidator())->validate(new class () implements DataInputInterface {});
    }
}
