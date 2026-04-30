<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\GetEquipmentDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Validator\Admin\Training\Equipment\GetEquipmentDetailsValidator;
use PHPUnit\Framework\TestCase;

final class GetEquipmentDetailsValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAValidInput(): void
    {
        (new GetEquipmentDetailsValidator())->validate(new GetEquipmentDetailsDataInput('any-id'));

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $this->expectException(\LogicException::class);

        (new GetEquipmentDetailsValidator())->validate(new class () implements DataInputInterface {});
    }
}
