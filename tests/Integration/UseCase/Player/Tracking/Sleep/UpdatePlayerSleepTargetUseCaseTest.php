<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdatePlayerSleepTargetDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Sleep\UpdatePlayerSleepTargetValidator;
use App\UseCase\Player\Tracking\Sleep\UpdatePlayerSleepTargetUseCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdatePlayerSleepTargetUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTheSleepTargetOnTheLoggedPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-sleep-target');
        self::assertSame(480, $player->dailySleepTargetMinutes);

        $output = self::buildUseCase($container, $player)->execute(new UpdatePlayerSleepTargetDataInput(420));

        self::assertSame(420, $output->dailySleepTargetMinutes);
        self::assertSame(420, $player->dailySleepTargetMinutes);
    }

    public function testItRejectsANonPositiveTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-sleep-target-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new UpdatePlayerSleepTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerSleepTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetMinutes', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdatePlayerSleepTargetUseCase
    {
        $resolver = self::stubResolver($player);

        return new UpdatePlayerSleepTargetUseCase(
            new UpdatePlayerSleepTargetValidator($resolver),
            $resolver,
            $container->get(PlayerPersisterGateway::class),
            $container->get(ObjectMapperInterface::class),
        );
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Sleep Hero',
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
