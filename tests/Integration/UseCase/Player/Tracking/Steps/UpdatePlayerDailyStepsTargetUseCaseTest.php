<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetValidator;
use App\UseCase\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetUseCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdatePlayerDailyStepsTargetUseCaseTest extends KernelTestCase
{
    public function testItUpdatesThePlayerGlobalDefault(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-player-steps-target');
        self::assertSame(5000, $player->dailyStepsTarget);

        $output = self::buildUseCase($container, $player)->execute(new UpdatePlayerDailyStepsTargetDataInput(11000));

        self::assertSame(11000, $output->dailyStepsTarget);
        self::assertSame(11000, $player->dailyStepsTarget);
    }

    public function testItRejectsANonPositiveTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-player-steps-target-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new UpdatePlayerDailyStepsTargetDataInput(-5));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerDailyStepsTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('target', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdatePlayerDailyStepsTargetUseCase
    {
        $resolver = self::stubResolver($player);

        return new UpdatePlayerDailyStepsTargetUseCase(
            new UpdatePlayerDailyStepsTargetValidator($resolver),
            $resolver,
            $container->get(PlayerPersisterGateway::class),
        );
    }

    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Steps Hero',
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
