<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\UpdateHydrationEntryValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateHydrationEntryValidatorTest extends TestCase
{
    private UpdateHydrationEntryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UpdateHydrationEntryValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveValue(): void
    {
        $this->validator->validate(new UpdateHydrationEntryDataInput('01HZX0000000000000000ENTRY', 300));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new UpdateHydrationEntryDataInput('01HZX0000000000000000ENTRY', 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateHydrationEntryValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueMl', $e->violations);
        }
    }

    public function testItRejectsANegativeValue(): void
    {
        try {
            $this->validator->validate(new UpdateHydrationEntryDataInput('01HZX0000000000000000ENTRY', -10));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateHydrationEntryValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueMl', $e->violations);
        }
    }
}
