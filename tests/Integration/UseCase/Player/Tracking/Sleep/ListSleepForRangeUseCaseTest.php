<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\ListSleepForRangeDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\LogSleepDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Sleep\ListSleepForRangeValidator;
use App\Domain\Validator\Player\Tracking\Sleep\LogSleepValidator;
use App\Infrastructure\Persister\Tracking\Sleep\SleepDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Sleep\SleepDailyEntryRepository;
use App\UseCase\Player\Tracking\Sleep\ListSleepForRangeUseCase;
use App\UseCase\Player\Tracking\Sleep\LogSleepUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListSleepForRangeUseCaseTest extends KernelTestCase
{
    public function testItListsEntriesWithinTheInclusiveRangeOrderedByDate(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-sleep-range');
        self::logSleep($container, $player, '2026-05-07T23:00:00Z', '2026-05-08T07:00:00Z');
        self::logSleep($container, $player, '2026-05-04T23:00:00Z', '2026-05-05T07:00:00Z');
        self::logSleep($container, $player, '2026-05-06T23:00:00Z', '2026-05-07T07:00:00Z');
        self::logSleep($container, $player, '2026-04-29T23:00:00Z', '2026-04-30T07:00:00Z');

        $output = self::buildUseCase($container, $player)->execute(new ListSleepForRangeDataInput(
            new \DateTimeImmutable('2026-05-05'),
            new \DateTimeImmutable('2026-05-08'),
        ));

        self::assertCount(3, $output);
        self::assertSame(
            (new \DateTimeImmutable('2026-05-05T07:00:00Z'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output[0]->date,
        );
        self::assertSame(
            (new \DateTimeImmutable('2026-05-07T07:00:00Z'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output[1]->date,
        );
        self::assertSame(
            (new \DateTimeImmutable('2026-05-08T07:00:00Z'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output[2]->date,
        );
    }

    public function testItReturnsAnEmptyListWhenNoEntriesMatch(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-sleep-empty');

        $output = self::buildUseCase($container, $player)->execute(new ListSleepForRangeDataInput(
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        self::assertSame([], $output);
    }

    public function testItRejectsAReversedRange(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-sleep-reversed');

        try {
            self::buildUseCase($container, $player)->execute(new ListSleepForRangeDataInput(
                new \DateTimeImmutable('2026-05-07'),
                new \DateTimeImmutable('2026-05-01'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListSleepForRangeValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('from', $e->violations);
        }
    }

    private static function logSleep(ContainerInterface $container, PlayerDataModel $player, string $bedAt, string $wakeAt): void
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new SleepDailyEntryRepository($registry);

        $logUseCase = new LogSleepUseCase(
            new LogSleepValidator($resolver, $repo),
            $resolver,
            new SleepDailyEntryPersister($em, $clock),
            $container->get(QuestProgressionEvaluator::class),
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
        );
        $logUseCase->execute(new LogSleepDataInput(new \DateTimeImmutable($bedAt), new \DateTimeImmutable($wakeAt)));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListSleepForRangeUseCase
    {
        $resolver = self::stubResolver($player);
        $registry = $container->get(ManagerRegistry::class);

        return new ListSleepForRangeUseCase(
            new ListSleepForRangeValidator(),
            $resolver,
            new SleepDailyEntryRepository($registry),
            self::getContainer()->get(ObjectMapperInterface::class),
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
