<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\AddHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\AddHydrationEntryValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class AddHydrationEntryValidatorTest extends TestCase
{
    private AddHydrationEntryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AddHydrationEntryValidator($this->createMock(LoggedPlayerResolverInterface::class));
    }

    public function testItPassesForAPositiveValue(): void
    {
        $this->validator->validate(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T10:00:00Z'), 250));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsZero(): void
    {
        try {
            $this->validator->validate(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T10:00:00Z'), 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddHydrationEntryValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueMl', $e->violations);
        }
    }

    public function testItRejectsANegativeValue(): void
    {
        try {
            $this->validator->validate(new AddHydrationEntryDataInput(new \DateTimeImmutable('2026-05-07T10:00:00Z'), -50));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(AddHydrationEntryValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueMl', $e->violations);
        }
    }
}
