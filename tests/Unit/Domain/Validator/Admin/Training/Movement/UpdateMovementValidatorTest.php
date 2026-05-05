<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Movement;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Movement\UpdateMovementDataInput;
use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Movement\UpdateMovementValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateMovementValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private MovementProviderGateway&MockObject $movementProviderGateway;
    private MuscleProviderGateway&MockObject $muscleProviderGateway;
    private EquipmentProviderGateway&MockObject $equipmentProviderGateway;
    private StringDataTransformerInterface&MockObject $stringDataTransformer;
    private UpdateMovementValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->movementProviderGateway = $this->createMock(MovementProviderGateway::class);
        $this->muscleProviderGateway = $this->createMock(MuscleProviderGateway::class);
        $this->equipmentProviderGateway = $this->createMock(EquipmentProviderGateway::class);
        $this->stringDataTransformer = $this->createMock(StringDataTransformerInterface::class);
        $this->validator = new UpdateMovementValidator(
            $this->loggedUserResolver,
            $this->movementProviderGateway,
            $this->muscleProviderGateway,
            $this->equipmentProviderGateway,
            $this->stringDataTransformer,
        );
    }

    public function testItPassesWhenSlugIsFreeOrBelongsToTheSameRow(): void
    {
        $self = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $self->id = 'mine';
        $this->stringDataTransformer->method('slugify')->willReturn('bench-press');
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn($self);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));
        $this->equipmentProviderGateway->method('findOneForAdminDetails')->willReturn(new EquipmentDataModel('Barbell'));

        $this->validator->validate(new UpdateMovementDataInput(
            'mine',
            'Bench press',
            'm-1',
            [],
            ['e-1'],
            true,
            true,
            false,
            false,
            false,
            false,
        ), $self);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsSlugBelongingToAnotherRow(): void
    {
        $mine = new MovementDataModel('Whatever', new MuscleDataModel('Chest'));
        $mine->id = 'mine';
        $other = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $other->id = 'other';
        $this->stringDataTransformer->method('slugify')->willReturn('bench-press');
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn($other);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));
        $this->equipmentProviderGateway->method('findOneForAdminDetails')->willReturn(new EquipmentDataModel('Barbell'));

        try {
            $this->validator->validate(new UpdateMovementDataInput(
                'mine',
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
            ), $mine);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another movement already uses this label.', $e->violations['label'] ?? []);
        }
    }

    public function testItAcceptsValidVideoAndGifUrls(): void
    {
        $self = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $self->id = 'mine';
        $this->stringDataTransformer->method('slugify')->willReturn('bench-press');
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn($self);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));

        $this->validator->validate(new UpdateMovementDataInput(
            'mine',
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
            'https://example.com/demo.mp4',
            'https://example.com/demo.gif',
        ), $self);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsInvalidVideoLink(): void
    {
        $self = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $self->id = 'mine';
        $this->stringDataTransformer->method('slugify')->willReturn('bench-press');
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn($self);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));

        try {
            $this->validator->validate(new UpdateMovementDataInput(
                'mine',
                'Bench press',
                'm-1',
                [],
                [],
                true,
                false,
                false,
                false,
                false,
                false,
                'not-a-url',
            ), $self);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('videoLink', $e->violations);
            self::assertContains('Video link must be a valid URL.', $e->violations['videoLink']);
        }
    }

    public function testItRejectsInvalidGifLink(): void
    {
        $self = new MovementDataModel('Bench press', new MuscleDataModel('Chest'));
        $self->id = 'mine';
        $this->stringDataTransformer->method('slugify')->willReturn('bench-press');
        $this->movementProviderGateway->method('findOneBySlugForUniqueness')->willReturn($self);
        $this->muscleProviderGateway->method('findOneForAdminDetails')->willReturn(new MuscleDataModel('Chest'));

        try {
            $this->validator->validate(new UpdateMovementDataInput(
                'mine',
                'Bench press',
                'm-1',
                [],
                [],
                true,
                false,
                false,
                false,
                false,
                false,
                null,
                'not-a-url',
            ), $self);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('gifLink', $e->violations);
            self::assertContains('GIF link must be a valid URL.', $e->violations['gifLink']);
        }
    }
}
