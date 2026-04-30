<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Validator\EmptyDomainValidator;
use PHPUnit\Framework\TestCase;

final class EmptyDomainValidatorTest extends TestCase
{
    public function testItDoesNotThrowForAnyInput(): void
    {
        $validator = new EmptyDomainValidator();
        $validator->validate(new class () implements DataInputInterface {});
        $validator->validate(new \stdClass());

        $this->expectNotToPerformAssertions();
    }
}
