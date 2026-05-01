<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\User;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\Validator\User\RegisterPlayerValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class RegisterPlayerValidatorTest extends TestCase
{
    private UserProviderGateway&MockObject $userProviderGateway;
    private RegisterPlayerValidator $validator;

    protected function setUp(): void
    {
        $this->userProviderGateway = $this->createMock(UserProviderGateway::class);
        $this->validator = new RegisterPlayerValidator($this->userProviderGateway);
    }

    public function testItPassesForFullyValidInput(): void
    {
        $this->userProviderGateway
            ->method('findOneByEmailForUniquenessCheck')
            ->willReturn(null);

        $this->validator->validate(new RegisterPlayerDataInput(
            'hero@akhilleus.test',
            'StrongPass1!',
            'Hero',
        ));

        // No exception thrown is the success case.
        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnInvalidEmailFormat(): void
    {
        $this->userProviderGateway
            ->expects(self::never())
            ->method('findOneByEmailForUniquenessCheck');

        try {
            $this->validator->validate(new RegisterPlayerDataInput(
                'not-an-email',
                'StrongPass1!',
                'Hero',
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(RegisterPlayerValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Email is not a valid address.', $e->violations['email'] ?? []);
        }
    }

    public function testItRejectsAnAlreadyTakenEmail(): void
    {
        $existing = new UserDataModel(
            'taken@akhilleus.test',
            'whatever',
            [UserRoleRegistry::ROLE_PLAYER],
        );
        $this->userProviderGateway
            ->expects(self::once())
            ->method('findOneByEmailForUniquenessCheck')
            ->with('taken@akhilleus.test')
            ->willReturn($existing);

        try {
            $this->validator->validate(new RegisterPlayerDataInput(
                'taken@akhilleus.test',
                'StrongPass1!',
                'Hero',
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains(
                'An account with this email already exists.',
                $e->violations['email'] ?? [],
            );
        }
    }

    public function testItRejectsAWhitespaceOnlyDisplayName(): void
    {
        $this->userProviderGateway
            ->method('findOneByEmailForUniquenessCheck')
            ->willReturn(null);

        try {
            $this->validator->validate(new RegisterPlayerDataInput(
                'hero@akhilleus.test',
                'StrongPass1!',
                "  \t\n ",
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains(
                'Display name must not be empty.',
                $e->violations['displayName'] ?? [],
            );
        }
    }

    public function testItRejectsAPasswordShorterThanMinimumLength(): void
    {
        $this->expectViolation('plainPassword', 'Password must be at least 8 characters.', 'Aa1!aaa');
    }

    public function testItRejectsAPasswordWithoutUppercase(): void
    {
        $this->expectViolation('plainPassword', 'Password must contain at least one uppercase letter.', 'lowercase1!');
    }

    public function testItRejectsAPasswordWithoutLowercase(): void
    {
        $this->expectViolation('plainPassword', 'Password must contain at least one lowercase letter.', 'UPPERCASE1!');
    }

    public function testItRejectsAPasswordWithoutDigit(): void
    {
        $this->expectViolation('plainPassword', 'Password must contain at least one digit.', 'NoDigitHere!');
    }

    public function testItRejectsAPasswordWithoutSpecialChar(): void
    {
        $this->expectViolation('plainPassword', 'Password must contain at least one special character.', 'NoSpecial1');
    }

    public function testItAccumulatesViolationsAcrossEveryField(): void
    {
        $this->userProviderGateway
            ->method('findOneByEmailForUniquenessCheck')
            ->willReturn(null);

        try {
            $this->validator->validate(new RegisterPlayerDataInput(
                'not-an-email',
                'weak',
                '',
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('email', $e->violations);
            self::assertArrayHasKey('displayName', $e->violations);
            self::assertArrayHasKey('plainPassword', $e->violations);
        }
    }

    private function expectViolation(string $field, string $expectedMessage, string $password): void
    {
        $this->userProviderGateway
            ->method('findOneByEmailForUniquenessCheck')
            ->willReturn(null);

        try {
            $this->validator->validate(new RegisterPlayerDataInput(
                'hero@akhilleus.test',
                $password,
                'Hero',
            ));
            self::fail(sprintf('Expected ValidationException for password "%s"', $password));
        } catch (ValidationException $e) {
            self::assertContains($expectedMessage, $e->violations[$field] ?? []);
        }
    }
}
