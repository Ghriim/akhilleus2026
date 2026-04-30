<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Movement;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Movement\CreateMovementValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class CreateMovementValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private MovementProviderGateway&MockObject $movementProviderGateway;
    private MuscleProviderGateway&MockObject $muscleProviderGateway;
    private EquipmentProviderGateway&MockObject $equipmentProviderGateway;
    private StringDataTransformerInterface&MockObject $stringDataTransformer;
    private CreateMovementValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->movementProviderGateway = $this->createMock(MovementProviderGateway::class);
        $this->muscleProviderGateway = $this->createMock(MuscleProviderGateway::class);
        $this->equipmentProviderGateway = $this->createMock(EquipmentProviderGateway::class);
        $this->stringDataTransformer = $this->createMock(StringDataTransformerInterface::class);
        $this->validator = new CreateMovementValidator(
            $this->loggedUserResolver,
            $this->movementProviderGateway,
            $this->muscleProviderGateway,
            $this->equipmentProviderGateway,
            $this->stringDataTransformer,
        );
    }

    public function testItPassesForFullyValidInput(): void
    {
        $this->stringDataTransformer->method('slugify')->willReturn('bench-press');
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn(null);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));
        $this->equipmentProviderGateway->method('findOneForAdminDetails')->willReturn(new EquipmentDataModel('Barbell'));

        $this->validator->validate(new CreateMovementDataInput(
            'Bench press',
            'm-1',
            ['m-2'],
            ['e-1'],
            true,
            true,
            false,
            false,
            false,
            false,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsEmptyLabelAndMissingTrackingFields(): void
    {
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));
        $this->equipmentProviderGateway->method('findOneForAdminDetails')->willReturn(new EquipmentDataModel('Barbell'));

        try {
            $this->validator->validate(new CreateMovementDataInput(
                '',
                'm-1',
                [],
                [],
                false,
                false,
                false,
                false,
                false,
                false,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateMovementValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('label', $e->violations);
            self::assertArrayHasKey('tracking', $e->violations);
        }
    }

    public function testItRejectsUnknownMainMuscleSecondaryAndEquipment(): void
    {
        $this->stringDataTransformer->method('slugify')->willReturn('movement');
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn(null);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(null);
        $this->equipmentProviderGateway->method('findOneForAdminDetails')->willReturn(null);

        try {
            $this->validator->validate(new CreateMovementDataInput(
                'Movement',
                'ghost-main',
                ['ghost-sec'],
                ['ghost-eq'],
                true,
                false,
                false,
                false,
                false,
                false,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('mainMuscleId', $e->violations);
            self::assertArrayHasKey('secondaryMuscleIds', $e->violations);
            self::assertArrayHasKey('equipmentIds', $e->violations);
        }
    }

    public function testItRejectsAlreadyTakenSlug(): void
    {
        $this->stringDataTransformer->method('slugify')->willReturn('bench-press');
        $other = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $other->id = 'existing-id';
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn($other);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));
        $this->equipmentProviderGateway->method('findOneForAdminDetails')->willReturn(new EquipmentDataModel('Barbell'));

        try {
            $this->validator->validate(new CreateMovementDataInput(
                'Bench press',
                'm-1',
                [],
                [],
                true,
                true,
                false,
                false,
                false,
                false,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another movement already uses this label.', $e->violations['label'] ?? []);
        }
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $this->expectException(\LogicException::class);

        $this->validator->validate(new class () implements DataInputInterface {});
    }
}
