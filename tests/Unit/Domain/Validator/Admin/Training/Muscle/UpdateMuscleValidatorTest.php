<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\UpdateMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Muscle\UpdateMuscleValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateMuscleValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private MuscleProviderGateway&MockObject $muscleProviderGateway;
    private StringDataTransformerInterface&MockObject $stringDataTransformer;
    private UpdateMuscleValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->muscleProviderGateway = $this->createMock(MuscleProviderGateway::class);
        $this->stringDataTransformer = $this->createMock(StringDataTransformerInterface::class);
        $this->validator = new UpdateMuscleValidator(
            $this->loggedUserResolver,
            $this->muscleProviderGateway,
            $this->stringDataTransformer,
        );
    }

    public function testItPassesWhenSlugIsFreeOrBelongsToTheSameRow(): void
    {
        $existing = new MuscleDataModel('Barbell');
        $existing->id = 'same-id';
        $this->stringDataTransformer->method('slugify')->willReturn('barbell');
        $this->muscleProviderGateway->method('findOneBySlugForUniqueness')->willReturn($existing);

        $this->validator->validate(new UpdateMuscleDataInput('same-id', 'Barbell'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsEmptyLabel(): void
    {
        try {
            $this->validator->validate(new UpdateMuscleDataInput('id', ''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateMuscleValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Label must not be empty.', $e->violations['label'] ?? []);
        }
    }

    public function testItRejectsSlugBelongingToAnotherRow(): void
    {
        $other = new MuscleDataModel('Barbell');
        $other->id = 'other-id';
        $this->stringDataTransformer->method('slugify')->willReturn('barbell');
        $this->muscleProviderGateway->method('findOneBySlugForUniqueness')->willReturn($other);

        try {
            $this->validator->validate(new UpdateMuscleDataInput('mine-id', 'Barbell'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Another muscle already uses this label.', $e->violations['label'] ?? []);
        }
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $this->expectException(\LogicException::class);

        $this->validator->validate(new class () implements DataInputInterface {});
    }
}
