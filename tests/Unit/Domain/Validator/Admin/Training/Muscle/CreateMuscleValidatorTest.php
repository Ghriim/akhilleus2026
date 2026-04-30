<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Muscle\CreateMuscleValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class CreateMuscleValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private MuscleProviderGateway&MockObject $muscleProviderGateway;
    private StringDataTransformerInterface&MockObject $stringDataTransformer;
    private CreateMuscleValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->muscleProviderGateway = $this->createMock(MuscleProviderGateway::class);
        $this->stringDataTransformer = $this->createMock(StringDataTransformerInterface::class);
        $this->validator = new CreateMuscleValidator(
            $this->loggedUserResolver,
            $this->muscleProviderGateway,
            $this->stringDataTransformer,
        );
    }

    public function testItPassesForValidLabel(): void
    {
        $this->stringDataTransformer->method('slugify')->willReturn('barbell');
        $this->muscleProviderGateway->method('findOneBySlugForUniqueness')->willReturn(null);

        $this->validator->validate(new CreateMuscleDataInput('Barbell'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsEmptyLabel(): void
    {
        try {
            $this->validator->validate(new CreateMuscleDataInput("  \t "));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateMuscleValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Label must not be empty.', $e->violations['label'] ?? []);
        }
    }

    public function testItRejectsAlreadyTakenSlug(): void
    {
        $existing = new MuscleDataModel('Barbell');
        $existing->id = 'existing-id';
        $this->stringDataTransformer->method('slugify')->willReturn('barbell');
        $this->muscleProviderGateway->method('findOneBySlugForUniqueness')->willReturn($existing);

        try {
            $this->validator->validate(new CreateMuscleDataInput('Barbell'));
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
