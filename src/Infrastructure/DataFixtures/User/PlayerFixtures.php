<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\User;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class PlayerFixtures extends Fixture
{
    public const string REFERENCE_PLAYER = 'player-';

    public function __construct(
        private readonly PlayerPersisterGateway $playerPersister,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $input = new RegisterPlayerDataInput(
            'player@akhilleus.test',
            'PlayerHero1!',
            'Player Hero'
        );

        $player = $this->playerPersister->create($input);
        $this->addReference(self::REFERENCE_PLAYER, $player);
    }
}
