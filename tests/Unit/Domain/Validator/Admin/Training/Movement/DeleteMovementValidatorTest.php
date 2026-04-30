<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\DeleteMovementDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Training\Movement\DeleteMovementValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class DeleteMovementValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAValidInput(): void
    {
        $validator = new DeleteMovementValidator($this->createMock(LoggedUserResolverInterface::class));

        $validator->validate(new DeleteMovementDataInput('any-id'));

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $validator = new DeleteMovementValidator($this->createMock(LoggedUserResolverInterface::class));

        $this->expectException(\LogicException::class);

        $validator->validate(new class () implements DataInputInterface {});
    }
}
