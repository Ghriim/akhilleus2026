<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdatePlayerWeightTargetDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\UpdatePlayerWeightTargetValidator;
use App\UseCase\Player\Tracking\Weight\UpdatePlayerWeightTargetUseCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdatePlayerWeightTargetUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTheWeightTargetOnTheLoggedPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-weight-target');
        self::assertNull($player->targetWeightGrams);

        $output = self::buildUseCase($container, $player)->execute(new UpdatePlayerWeightTargetDataInput(72000));

        self::assertSame(72000, $output->targetWeightGrams);
        self::assertSame(72000, $player->targetWeightGrams);
    }

    public function testItRejectsANonPositiveTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-weight-target-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new UpdatePlayerWeightTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdatePlayerWeightTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('targetGrams', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdatePlayerWeightTargetUseCase
    {
        $resolver = self::stubResolver($player);

        return new UpdatePlayerWeightTargetUseCase(
            new UpdatePlayerWeightTargetValidator($resolver),
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
            'Weight Hero',
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
