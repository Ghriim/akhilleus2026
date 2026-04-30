<?php

declare(strict_types=1);

namespace App\Domain\Validator\User;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validator\DomainValidatorInterface;

final readonly class RegisterPlayerValidator implements DomainValidatorInterface
{
    public const string ERROR_CODE = 'REGISTER_PLAYER_VALIDATION_FAILED';

    private const int PASSWORD_MIN_LENGTH = 8;

    public function __construct(
        private UserProviderGateway $userProviderGateway,
    ) {
    }

    public function validate(RegisterPlayerDataInput|DataInputInterface $input): void
    {
        if (false === $input instanceof RegisterPlayerDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', RegisterPlayerDataInput::class, $input::class));
        }

        $violations = [];
        if (false === filter_var($input->email, FILTER_VALIDATE_EMAIL)) {
            $violations['email'][] = 'Email is not a valid address.';
        } elseif (null !== $this->userProviderGateway->findOneByEmailForUniquenessCheck($input->email)) {
            $violations['email'][] = 'An account with this email already exists.';
        }

        if (true === empty(trim($input->displayName))) {
            $violations['displayName'][] = 'Display name must not be empty.';
        }

        if (self::PASSWORD_MIN_LENGTH > strlen($input->plainPassword)) {
            $violations['plainPassword'][] = sprintf(
                'Password must be at least %d characters.',
                self::PASSWORD_MIN_LENGTH,
            );
        }
        if (1 !== preg_match('/[A-Z]/', $input->plainPassword)) {
            $violations['plainPassword'][] = 'Password must contain at least one uppercase letter.';
        }
        if (1 !== preg_match('/[a-z]/', $input->plainPassword)) {
            $violations['plainPassword'][] = 'Password must contain at least one lowercase letter.';
        }
        if (1 !== preg_match('/\d/', $input->plainPassword)) {
            $violations['plainPassword'][] = 'Password must contain at least one digit.';
        }
        if (1 !== preg_match('/[^A-Za-z0-9]/', $input->plainPassword)) {
            $violations['plainPassword'][] = 'Password must contain at least one special character.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Player registration data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
