<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdateWeightDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Weight\WeightEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\LogWeightValidator;
use App\Domain\Validator\Player\Tracking\Weight\UpdateWeightValidator;
use App\Infrastructure\Persister\Tracking\Weight\WeightEntryPersister;
use App\Infrastructure\Repository\Tracking\Weight\WeightEntryRepository;
use App\UseCase\Player\Tracking\Weight\LogWeightUseCase;
use App\UseCase\Player\Tracking\Weight\UpdateWeightUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateWeightUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTheValueAndLoggedAt(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-weight-happy');
        $logged = self::logWeight($container, $player, '2026-05-07T08:00:00Z', 82000);

        $output = self::buildUseCase($container, $player)->execute(new UpdateWeightDataInput(
            $logged->id,
            new \DateTimeImmutable('2026-05-07T21:00:00Z'),
            81000,
        ));

        self::assertSame($logged->id, $output->id);
        self::assertSame(81000, $output->valueGrams);
        self::assertSame(
            (new \DateTimeImmutable('2026-05-07'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output->date,
        );
    }

    public function testItThrowsWhenTheEntryBelongsToAnotherPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $owner = self::createTestPlayer($container, 'update-weight-owner');
        $intruder = self::createTestPlayer($container, 'update-weight-intruder');
        $logged = self::logWeight($container, $owner, '2026-05-07T08:00:00Z', 82000);

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $intruder)->execute(new UpdateWeightDataInput(
            $logged->id,
            new \DateTimeImmutable('2026-05-07T09:00:00Z'),
            80000,
        ));
    }

    public function testItRejectsANonPositiveWeight(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-weight-invalid');
        $logged = self::logWeight($container, $player, '2026-05-07T08:00:00Z', 82000);

        try {
            self::buildUseCase($container, $player)->execute(new UpdateWeightDataInput(
                $logged->id,
                new \DateTimeImmutable('2026-05-07T09:00:00Z'),
                -1,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateWeightValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueGrams', $e->violations);
        }
    }

    private static function logWeight(ContainerInterface $container, PlayerDataModel $player, string $loggedAt, int $valueGrams): WeightEntryDataOutput
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new WeightEntryRepository($registry);

        $logUseCase = new LogWeightUseCase(
            new LogWeightValidator($resolver, $repo),
            $resolver,
            new WeightEntryPersister($em, $clock),
            self::getContainer()->get(ObjectMapperInterface::class),
        );

        return $logUseCase->execute(new LogWeightDataInput(new \DateTimeImmutable($loggedAt), $valueGrams));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdateWeightUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new WeightEntryRepository($registry);

        return new UpdateWeightUseCase(
            new UpdateWeightValidator($resolver, $repo),
            $resolver,
            $repo,
            new WeightEntryPersister($em, $clock),
            self::getContainer()->get(ObjectMapperInterface::class),
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
