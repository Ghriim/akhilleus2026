<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\DeleteMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Muscle\DeleteMuscleValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class DeleteMuscleValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAnyValidInput(): void
    {
        $validator = new DeleteMuscleValidator($this->createMock(LoggedUserResolverInterface::class));

        $validator->validate(new DeleteMuscleDataInput('any-id'));
        $validator->validate(new DeleteMuscleDataInput(''));

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $validator = new DeleteMuscleValidator($this->createMock(LoggedUserResolverInterface::class));

        $this->expectException(\LogicException::class);

        $validator->validate(new class () implements DataInputInterface {});
    }
}
