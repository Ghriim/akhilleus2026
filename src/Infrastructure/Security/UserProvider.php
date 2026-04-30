<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<UserDataModel>
 */
final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userProviderGateway->findOneByEmailForAuthentication($identifier);

        if (null === $user) {
            throw new UserNotFoundException(sprintf('User with email "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (false === $user instanceof UserDataModel) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return UserDataModel::class === $class || is_subclass_of($class, UserDataModel::class);
    }
}
