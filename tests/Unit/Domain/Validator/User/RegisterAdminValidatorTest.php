<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\User;

use App\Domain\DTO\DataInput\User\RegisterAdminDataInput;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\Validator\User\RegisterAdminValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class RegisterAdminValidatorTest extends TestCase
{
    private UserProviderGateway&MockObject $userProviderGateway;
    private RegisterAdminValidator $validator;

    protected function setUp(): void
    {
        $this->userProviderGateway = $this->createMock(UserProviderGateway::class);
        $this->validator = new RegisterAdminValidator($this->userProviderGateway);
    }

    public function testItPassesForFullyValidInput(): void
    {
        $this->userProviderGateway
            ->method('findOneByEmailForUniquenessCheck')
            ->willReturn(null);

        $this->validator->validate($this->input());

        // No exception thrown is the success case.
        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnInvalidEmailFormat(): void
    {
        $this->userProviderGateway
            ->expects(self::never())
            ->method('findOneByEmailForUniquenessCheck');

        try {
            $this->validator->validate($this->input(email: 'not-an-email'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(RegisterAdminValidator::ERROR_CODE, $e->errorCode);
            self::assertContains('Email is not a valid address.', $e->violations['email'] ?? []);
        }
    }

    public function testItRejectsAnAlreadyTakenEmail(): void
    {
        $existing = new UserDataModel(
            'taken@akhilleus.test',
            'whatever',
            [UserRoleRegistry::ROLE_ADMIN],
        );
        $this->userProviderGateway
            ->expects(self::once())
            ->method('findOneByEmailForUniquenessCheck')
            ->with('taken@akhilleus.test')
            ->willReturn($existing);

        try {
            $this->validator->validate($this->input(email: 'taken@akhilleus.test'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains(
                'An account with this email already exists.',
                $e->violations['email'] ?? [],
            );
        }
    }

    public function testItRejectsAWhitespaceOnlyFirstName(): void
    {
        $this->userProviderGateway->method('findOneByEmailForUniquenessCheck')->willReturn(null);

        try {
            $this->validator->validate($this->input(firstName: "  \t\n "));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('First name must not be empty.', $e->violations['firstName'] ?? []);
        }
    }

    public function testItRejectsAWhitespaceOnlyLastName(): void
    {
        $this->userProviderGateway->method('findOneByEmailForUniquenessCheck')->willReturn(null);

        try {
            $this->validator->validate($this->input(lastName: '   '));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Last name must not be empty.', $e->violations['lastName'] ?? []);
        }
    }

    public function testItRejectsAWhitespaceOnlyJobTitle(): void
    {
        $this->userProviderGateway->method('findOneByEmailForUniquenessCheck')->willReturn(null);

        try {
            $this->validator->validate($this->input(jobTitle: ''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertContains('Job title must not be empty.', $e->violations['jobTitle'] ?? []);
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
        $this->userProviderGateway->method('findOneByEmailForUniquenessCheck')->willReturn(null);

        try {
            $this->validator->validate($this->input(
                email: 'not-an-email',
                plainPassword: 'weak',
                firstName: '',
                lastName: '',
                jobTitle: '',
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('email', $e->violations);
            self::assertArrayHasKey('firstName', $e->violations);
            self::assertArrayHasKey('lastName', $e->violations);
            self::assertArrayHasKey('jobTitle', $e->violations);
            self::assertArrayHasKey('plainPassword', $e->violations);
        }
    }

    private function expectViolation(string $field, string $expectedMessage, string $password): void
    {
        $this->userProviderGateway->method('findOneByEmailForUniquenessCheck')->willReturn(null);

        try {
            $this->validator->validate($this->input(plainPassword: $password));
            self::fail(sprintf('Expected ValidationException for password "%s"', $password));
        } catch (ValidationException $e) {
            self::assertContains($expectedMessage, $e->violations[$field] ?? []);
        }
    }

    private function input(
        string $email = 'admin@akhilleus.test',
        string $plainPassword = 'StrongPass1!',
        string $firstName = 'Admin',
        string $lastName = 'User',
        string $jobTitle = 'Administrator',
    ): RegisterAdminDataInput {
        return new RegisterAdminDataInput(
            $email,
            $plainPassword,
            $firstName,
            $lastName,
            $jobTitle,
            new \DateTimeImmutable('2026-01-01'),
        );
    }
}
