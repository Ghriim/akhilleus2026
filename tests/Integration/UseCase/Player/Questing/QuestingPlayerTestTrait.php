<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use Psr\Container\ContainerInterface;

trait QuestingPlayerTestTrait
{
    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Questing Hero',
        ));
    }

    private static function seedQuest(
        ContainerInterface $container,
        string $kind,
        string $periodicity,
        ?string $metric = null,
        ?string $targetValue = null,
        int $rewardedXp = 100,
        ?\DateTimeImmutable $dateEnd = null,
    ): QuestDataModel {
        return $container->get(QuestPersisterGateway::class)->create(new QuestDataModel(
            'Test '.$periodicity.' quest '.uniqid('', true),
            $kind,
            $periodicity,
            new \DateTimeImmutable('2026-01-01T00:00:00Z'),
            $rewardedXp,
            $metric,
            $targetValue,
            $dateEnd,
        ));
    }

    private static function stubResolver(PlayerDataModel $player): LoggedPlayerResolverInterface
    {
        return new class ($player) implements LoggedPlayerResolverInterface {
            public function __construct(private PlayerDataModel $player)
            {
            }

            public function getLoggedPlayer(): PlayerDataModel
            {
                return $this->player;
            }
        };
    }
}
