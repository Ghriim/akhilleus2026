<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\ListWeightForRangeDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\ListWeightForRangeValidator;
use App\Domain\Validator\Player\Tracking\Weight\LogWeightValidator;
use App\Infrastructure\Persister\Tracking\Weight\WeightEntryPersister;
use App\Infrastructure\Repository\Tracking\Weight\WeightEntryRepository;
use App\UseCase\Player\Tracking\Weight\ListWeightForRangeUseCase;
use App\UseCase\Player\Tracking\Weight\LogWeightUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListWeightForRangeUseCaseTest extends KernelTestCase
{
    public function testItListsEntriesWithinTheInclusiveRangeOrderedByLoggedAt(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-weight-range');
        self::logWeight($container, $player, '2026-05-08T07:00:00Z', 81000);
        self::logWeight($container, $player, '2026-05-05T07:00:00Z', 82000);
        self::logWeight($container, $player, '2026-05-07T07:00:00Z', 81500);
        self::logWeight($container, $player, '2026-04-30T07:00:00Z', 83000);

        // The closing bound is a bare date; an entry logged late on that day must still be included.
        $output = self::buildUseCase($container, $player)->execute(new ListWeightForRangeDataInput(
            new \DateTimeImmutable('2026-05-05'),
            new \DateTimeImmutable('2026-05-08'),
        ));

        self::assertCount(3, $output);
        self::assertSame(82000, $output[0]->valueGrams);
        self::assertSame(81500, $output[1]->valueGrams);
        self::assertSame(81000, $output[2]->valueGrams);
    }

    public function testItIncludesAnEntryLoggedLateOnTheClosingDay(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-weight-endofday');
        self::logWeight($container, $player, '2026-05-08T23:30:00Z', 80000);

        $output = self::buildUseCase($container, $player)->execute(new ListWeightForRangeDataInput(
            new \DateTimeImmutable('2026-05-08'),
            new \DateTimeImmutable('2026-05-08'),
        ));

        self::assertCount(1, $output);
        self::assertSame(80000, $output[0]->valueGrams);
    }

    public function testItReturnsAnEmptyListWhenNoEntriesMatch(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-weight-empty');

        $output = self::buildUseCase($container, $player)->execute(new ListWeightForRangeDataInput(
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        self::assertSame([], $output);
    }

    public function testItRejectsAReversedRange(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-weight-reversed');

        try {
            self::buildUseCase($container, $player)->execute(new ListWeightForRangeDataInput(
                new \DateTimeImmutable('2026-05-07'),
                new \DateTimeImmutable('2026-05-01'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListWeightForRangeValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('from', $e->violations);
        }
    }

    private static function logWeight(ContainerInterface $container, PlayerDataModel $player, string $loggedAt, int $valueGrams): void
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
        );
        $logUseCase->execute(new LogWeightDataInput(new \DateTimeImmutable($loggedAt), $valueGrams));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListWeightForRangeUseCase
    {
        $resolver = self::stubResolver($player);
        $registry = $container->get(ManagerRegistry::class);

        return new ListWeightForRangeUseCase(
            new ListWeightForRangeValidator(),
            $resolver,
            new WeightEntryRepository($registry),
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
