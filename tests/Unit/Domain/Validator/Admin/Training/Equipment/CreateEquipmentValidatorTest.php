<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Equipment\CreateEquipmentValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class CreateEquipmentValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private EquipmentProviderGateway&MockObject $equipmentProviderGateway;
    private StringDataTransformerInterface&MockObject $stringDataTransformer;
    private CreateEquipmentValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->equipmentProviderGateway = $this->createMock(EquipmentProviderGateway::class);
        $this->stringDataTransformer = $this->createMock(StringDataTransformerInterface::class);
        $this->validator = new CreateEquipmentValidator(
            $this->loggedUserResolver,
            $this->equipmentProviderGateway,
            $this->stringDataTransformer,
        );
    }

    public function testItPassesForValidLabel(): void
    {
        $this->stringDataTransformer->method('slugify')->willReturn('barbell');
        $this->equipmentProviderGateway->method('findOneBySlugForUniqueness')->willReturn(null);

        $this->validator->validate(new CreateEquipmentDataInput('Barbell'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsEmptyLabel(): void
    {
        try {
            $this->validator->validate(new CreateEquipmentDataInput("  \t "));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateEquipmentValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Label must not be empty.', $e->violations['label'] ?? []);
        }
    }

    public function testItRejectsAlreadyTakenSlug(): void
    {
        $existing = new EquipmentDataModel('Barbell');
        $existing->id = 'existing-id';
        $this->stringDataTransformer->method('slugify')->willReturn('barbell');
        $this->equipmentProviderGateway->method('findOneBySlugForUniqueness')->willReturn($existing);

        try {
            $this->validator->validate(new CreateEquipmentDataInput('Barbell'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another equipment already uses this label.', $e->violations['label'] ?? []);
        }
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $this->expectException(\LogicException::class);

        $this->validator->validate(new class () implements DataInputInterface {});
    }
}
