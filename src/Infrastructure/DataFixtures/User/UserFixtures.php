<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\User;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Ulid;

final class UserFixtures extends Fixture
{
    public const string REFERENCE_ADMIN_USER = 'user-admin';
    public const string REFERENCE_PLAYER_USER = 'user-player';
    public const string REFERENCE_PLAYER_PROFILE = 'player-profile';

    public function __construct(
        private readonly UserPersisterGateway $userPersister,
        private readonly PlayerPersisterGateway $playerPersister,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new UserDataModel(
            (string) new Ulid(),
            'admin@akhilleus.test',
            'AdminAdmin1!',
            [UserRoleRegistry::ROLE_ADMIN],
        );
        $this->userPersister->create($admin);
        $this->addReference(self::REFERENCE_ADMIN_USER, $admin);

        $playerUser = new UserDataModel(
            (string) new Ulid(),
            'player@akhilleus.test',
            'PlayerHero1!',
            [UserRoleRegistry::ROLE_PLAYER],
        );
        $this->userPersister->create($playerUser);
        $this->addReference(self::REFERENCE_PLAYER_USER, $playerUser);

        $player = new PlayerDataModel(
            (string) new Ulid(),
            $playerUser,
            'Player Hero',
        );
        $this->playerPersister->create($player);
        $this->addReference(self::REFERENCE_PLAYER_PROFILE, $player);
    }
}
