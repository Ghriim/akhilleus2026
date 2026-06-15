<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\LogSleepDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Sleep\LogSleepValidator;
use App\Infrastructure\Persister\Tracking\Sleep\SleepDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Sleep\SleepDailyEntryRepository;
use App\UseCase\Player\Tracking\Sleep\LogSleepUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LogSleepUseCaseTest extends KernelTestCase
{
    public function testItLogsANightAndComputesTheDuration(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'log-sleep-happy');
        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new LogSleepDataInput(
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
            4,
        ));

        self::assertNotEmpty($output->id);
        self::assertSame(480, $output->durationMinutes);
        self::assertSame(4, $output->quality);
        self::assertSame(
            (new \DateTimeImmutable('2026-05-07T07:00:00Z'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output->date,
        );

        $repo = new SleepDailyEntryRepository($container->get(ManagerRegistry::class));
        self::assertNotNull($repo->findOneByPlayerAndDate($player, new \DateTimeImmutable('2026-05-07')));
    }

    public function testItLogsANightWithoutAQualityScore(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'log-sleep-noquality');
        $output = self::buildUseCase($container, $player)->execute(new LogSleepDataInput(
            new \DateTimeImmutable('2026-05-06T23:30:00Z'),
            new \DateTimeImmutable('2026-05-07T06:30:00Z'),
        ));

        self::assertNull($output->quality);
        self::assertSame(420, $output->durationMinutes);
    }

    public function testItRejectsWakeBeforeBed(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'log-sleep-reversed');

        try {
            self::buildUseCase($container, $player)->execute(new LogSleepDataInput(
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('wakeAt', $e->violations);
        }
    }

    public function testItRejectsADuplicateNight(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'log-sleep-duplicate');
        $useCase = self::buildUseCase($container, $player);

        $useCase->execute(new LogSleepDataInput(
            new \DateTimeImmutable('2026-05-06T23:00:00Z'),
            new \DateTimeImmutable('2026-05-07T07:00:00Z'),
            3,
        ));

        try {
            $useCase->execute(new LogSleepDataInput(
                new \DateTimeImmutable('2026-05-06T22:00:00Z'),
                new \DateTimeImmutable('2026-05-07T06:00:00Z'),
                4,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(LogSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('date', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): LogSleepUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new SleepDailyEntryRepository($registry);

        return new LogSleepUseCase(
            new LogSleepValidator($resolver, $repo),
            $resolver,
            new SleepDailyEntryPersister($em, $clock),
            $container->get(QuestProgressionEvaluator::class),
            $clock,
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
