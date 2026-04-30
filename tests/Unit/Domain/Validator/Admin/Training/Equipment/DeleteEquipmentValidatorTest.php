<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\DeleteEquipmentDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Equipment\DeleteEquipmentValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class DeleteEquipmentValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAnyValidInput(): void
    {
        $validator = new DeleteEquipmentValidator($this->createMock(LoggedUserResolverInterface::class));

        $validator->validate(new DeleteEquipmentDataInput('any-id'));
        $validator->validate(new DeleteEquipmentDataInput(''));

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $validator = new DeleteEquipmentValidator($this->createMock(LoggedUserResolverInterface::class));

        $this->expectException(\LogicException::class);

        $validator->validate(new class () implements DataInputInterface {});
    }
}
