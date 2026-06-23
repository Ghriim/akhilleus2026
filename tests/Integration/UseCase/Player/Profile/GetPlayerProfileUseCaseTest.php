<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Profile;

use App\Domain\DTO\DataInput\Player\Profile\GetPlayerProfileDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\Player\Profile\GetPlayerProfileUseCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class GetPlayerProfileUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheLoggedPlayersProfileWithLevelingBaseline(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $player = self::createTestPlayer($container, 'profile-baseline');

        $output = self::buildUseCase($container, $player)->execute(new GetPlayerProfileDataInput());

        self::assertSame($player->id, $output->id);
        self::assertSame('Profile Hero', $output->displayName);
        self::assertSame(1, $output->level);
        self::assertSame(0, $output->currentXp);
        self::assertSame(4000, $output->xpToNextLevel);
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Profile Hero',
        ));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): GetPlayerProfileUseCase
    {
        $resolver = new class ($player) implements LoggedPlayerResolverInterface {
            public function __construct(private PlayerDataModel $player)
            {
            }

            public function getLoggedPlayer(): PlayerDataModel
            {
                return $this->player;
            }
        };

        return new GetPlayerProfileUseCase($resolver, self::getContainer()->get(ObjectMapperInterface::class));
    }
}
