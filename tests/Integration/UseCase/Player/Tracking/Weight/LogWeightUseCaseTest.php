<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\LogWeightValidator;
use App\Infrastructure\Persister\Tracking\Weight\WeightEntryPersister;
use App\Infrastructure\Repository\Tracking\Weight\WeightEntryRepository;
use App\UseCase\Player\Tracking\Weight\LogWeightUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class LogWeightUseCaseTest extends KernelTestCase
{
    public function testItLogsAWeightAndDerivesTheDate(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'log-weight-happy');
        $output = self::buildUseCase($container, $player)->execute(new LogWeightDataInput(
            new \DateTimeImmutable('2026-05-07T08:30:00Z'),
            82000,
        ));

        self::assertNotEmpty($output->id);
        self::assertSame(82000, $output->valueGrams);
        self::assertSame(
            (new \DateTimeImmutable('2026-05-07T08:30:00Z'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output->date,
        );

        $repo = new WeightEntryRepository($container->get(ManagerRegistry::class));
        self::assertNotNull($repo->findOneByPlayerAndDate($player, new \DateTimeImmutable('2026-05-07')));
    }

    public function testItRejectsANonPositiveWeight(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'log-weight-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new LogWeightDataInput(new \DateTimeImmutable('2026-05-07T08:00:00Z'), 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogWeightValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('valueGrams', $e->violations);
        }
    }

    public function testItRejectsASecondEntryOnTheSameDay(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'log-weight-duplicate');
        $useCase = self::buildUseCase($container, $player);

        $useCase->execute(new LogWeightDataInput(new \DateTimeImmutable('2026-05-07T08:00:00Z'), 82000));

        try {
            $useCase->execute(new LogWeightDataInput(new \DateTimeImmutable('2026-05-07T21:00:00Z'), 81500));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogWeightValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('date', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): LogWeightUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new WeightEntryRepository($registry);

        return new LogWeightUseCase(
            new LogWeightValidator($resolver, $repo),
            $resolver,
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
