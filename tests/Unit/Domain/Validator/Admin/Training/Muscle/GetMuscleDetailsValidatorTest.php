<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\GetMuscleDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Validator\Admin\Training\Muscle\GetMuscleDetailsValidator;
use PHPUnit\Framework\TestCase;

final class GetMuscleDetailsValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAValidInput(): void
    {
        (new GetMuscleDetailsValidator())->validate(new GetMuscleDetailsDataInput('any-id'));

        $this->expectNotToPerformAssertions();
    }

    public function testItThrowsLogicExceptionForWrongInputType(): void
    {
        $this->expectException(\LogicException::class);

        (new GetMuscleDetailsValidator())->validate(new class () implements DataInputInterface {});
    }
}
