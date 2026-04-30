<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\User;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\PlayerProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\Validator\User\RegisterPlayerValidator;
use App\UseCase\User\RegisterPlayerUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RegisterPlayerUseCaseTest extends KernelTestCase
{
    public function testItRegistersAValidPlayerAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $useCase = $container->get(RegisterPlayerUseCase::class);
        $userProviderGateway = $container->get(UserProviderGateway::class);
        $playerProviderGateway = $container->get(PlayerProviderGateway::class);

        $output = $useCase->execute(new RegisterPlayerDataInput(
            'register-test-happy@akhilleus.test',
            'StrongPass1!',
            'Happy Hero',
        ));

        self::assertSame('register-test-happy@akhilleus.test', $output->email);
        self::assertSame('Happy Hero', $output->displayName);
        self::assertNotEmpty($output->playerId);

        $user = $userProviderGateway->findOneByEmailForUniquenessCheck('register-test-happy@akhilleus.test');
        self::assertNotNull($user);
        self::assertSame([UserRoleRegistry::ROLE_PLAYER], $user->roles);
        self::assertNotSame('StrongPass1!', $user->password, 'password must be hashed by the persister');

        $player = $playerProviderGateway->findOneByUserForLoggedPlayer($user);
        self::assertNotNull($player);
        self::assertSame($output->playerId, $player->id);
        self::assertSame('Happy Hero', $player->displayName);
    }

    public function testItRejectsAnInvalidEmailFormat(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(RegisterPlayerUseCase::class);

        try {
            $useCase->execute(new RegisterPlayerDataInput(
                'not-an-email',
                'StrongPass1!',
                'Hero',
            ));
            self::fail('Expected ValidationException for invalid email');
        } catch (ValidationException $e) {
            self::assertSame(RegisterPlayerValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('email', $e->violations);
            self::assertContains('Email is not a valid address.', $e->violations['email']);
        }
    }

    public function testItRejectsAnAlreadyTakenEmail(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(RegisterPlayerUseCase::class);

        $useCase->execute(new RegisterPlayerDataInput(
            'register-test-duplicate@akhilleus.test',
            'StrongPass1!',
            'First Hero',
        ));

        try {
            $useCase->execute(new RegisterPlayerDataInput(
                'register-test-duplicate@akhilleus.test',
                'OtherPass1!',
                'Second Hero',
            ));
            self::fail('Expected ValidationException for duplicate email');
        } catch (ValidationException $e) {
            self::assertSame(RegisterPlayerValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('email', $e->violations);
            self::assertContains('An account with this email already exists.', $e->violations['email']);
        }
    }

    public function testItRejectsAnEmptyDisplayName(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(RegisterPlayerUseCase::class);

        try {
            $useCase->execute(new RegisterPlayerDataInput(
                'register-test-noname@akhilleus.test',
                'StrongPass1!',
                '   ',
            ));
            self::fail('Expected ValidationException for empty displayName');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('displayName', $e->violations);
            self::assertContains('Display name must not be empty.', $e->violations['displayName']);
        }
    }

    public function testItAccumulatesEveryPasswordViolationInOneException(): void
    {
        self::bootKernel();
        $useCase = self::getContainer()->get(RegisterPlayerUseCase::class);

        try {
            $useCase->execute(new RegisterPlayerDataInput(
                'register-test-weakpass@akhilleus.test',
                'weak', // 4 chars, all lowercase, no digit, no special char
                'Weak Hero',
            ));
            self::fail('Expected ValidationException for weak password');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('plainPassword', $e->violations);
            $violations = $e->violations['plainPassword'];
            self::assertContains('Password must be at least 8 characters.', $violations);
            self::assertContains('Password must contain at least one uppercase letter.', $violations);
            self::assertContains('Password must contain at least one digit.', $violations);
            self::assertContains('Password must contain at least one special character.', $violations);
            self::assertNotContains(
                'Password must contain at least one lowercase letter.',
                $violations,
                '"weak" already contains lowercase letters — the rule must not fire.',
            );
        }
    }
}
