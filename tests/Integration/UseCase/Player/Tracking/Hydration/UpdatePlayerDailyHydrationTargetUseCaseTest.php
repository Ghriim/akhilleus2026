<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetValidator;
use App\UseCase\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetUseCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdatePlayerDailyHydrationTargetUseCaseTest extends KernelTestCase
{
    public function testItUpdatesThePlayerGlobalDefault(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-player-target');
        self::assertSame(1000, $player->dailyHydrationTargetMl);

        $output = self::buildUseCase($container, $player)->execute(new UpdatePlayerDailyHydrationTargetDataInput(2750));

        self::assertSame(2750, $output->dailyHydrationTargetMl);
        self::assertSame(2750, $player->dailyHydrationTargetMl);
    }

    public function testItRejectsANonPositiveTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-player-target-invalid');
        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new UpdatePlayerDailyHydrationTargetDataInput(-5));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerDailyHydrationTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMl', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdatePlayerDailyHydrationTargetUseCase
    {
        $resolver = self::stubResolver($player);

        return new UpdatePlayerDailyHydrationTargetUseCase(
            new UpdatePlayerDailyHydrationTargetValidator($resolver),
            $resolver,
            $container->get(PlayerPersisterGateway::class),
        );
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Hydration Hero',
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
