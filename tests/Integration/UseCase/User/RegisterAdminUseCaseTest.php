<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\User;

use App\Domain\DTO\DataInput\User\RegisterAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\Validator\User\RegisterAdminValidator;
use App\UseCase\User\RegisterAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RegisterAdminUseCaseTest extends KernelTestCase
{
    public function testItRegistersAValidAdminAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $useCase = $container->get(RegisterAdminUseCase::class);
        $userProviderGateway = $container->get(UserProviderGateway::class);

        $output = $useCase->execute(new RegisterAdminDataInput(
            'register-admin-happy@akhilleus.test',
            'StrongPass1!',
            'Happy',
            'Admin',
            'Platform Administrator',
            new \DateTimeImmutable('2026-01-01'),
        ));

        self::assertSame('register-admin-happy@akhilleus.test', $output->email);
        self::assertNotEmpty($output->id);

        $user = $userProviderGateway->findOneByEmailForUniquenessCheck('register-admin-happy@akhilleus.test');
        self::assertNotNull($user);
        self::assertSame([UserRoleRegistry::ROLE_ADMIN], $user->roles);
        self::assertNotSame('StrongPass1!', $user->password, 'password must be hashed by the persister');
    }

    public function testItRejectsAnInvalidEmailFormat(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(RegisterAdminUseCase::class);

        try {
            $useCase->execute(new RegisterAdminDataInput(
                'not-an-email',
                'StrongPass1!',
                'Happy',
                'Admin',
                'Administrator',
                new \DateTimeImmutable('2026-01-01'),
            ));
            self::fail('Expected ValidationException for invalid email');
        } catch (ValidationException $e) {
            self::assertSame(RegisterAdminValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('email', $e->violations);
            self::assertContains('Email is not a valid address.', $e->violations['email']);
        }
    }

    public function testItRejectsAnAlreadyTakenEmail(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(RegisterAdminUseCase::class);

        $useCase->execute(new RegisterAdminDataInput(
            'register-admin-duplicate@akhilleus.test',
            'StrongPass1!',
            'First',
            'Admin',
            'Administrator',
            new \DateTimeImmutable('2026-01-01'),
        ));

        try {
            $useCase->execute(new RegisterAdminDataInput(
                'register-admin-duplicate@akhilleus.test',
                'OtherPass1!',
                'Second',
                'Admin',
                'Administrator',
                new \DateTimeImmutable('2026-01-01'),
            ));
            self::fail('Expected ValidationException for duplicate email');
        } catch (ValidationException $e) {
            self::assertSame(RegisterAdminValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('email', $e->violations);
            self::assertContains('An account with this email already exists.', $e->violations['email']);
        }
    }

    public function testItRejectsAWeakPassword(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(RegisterAdminUseCase::class);

        try {
            $useCase->execute(new RegisterAdminDataInput(
                'register-admin-weak@akhilleus.test',
                'weak',
                'Happy',
                'Admin',
                'Administrator',
                new \DateTimeImmutable('2026-01-01'),
            ));
            self::fail('Expected ValidationException for weak password');
        } catch (ValidationException $e) {
            self::assertSame(RegisterAdminValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('plainPassword', $e->violations);
        }
    }
}
