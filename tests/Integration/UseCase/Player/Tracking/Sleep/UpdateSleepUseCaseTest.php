<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\LogSleepDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdateSleepDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\SleepDailyEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Sleep\LogSleepValidator;
use App\Domain\Validator\Player\Tracking\Sleep\UpdateSleepValidator;
use App\Infrastructure\Persister\Tracking\Sleep\SleepDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Sleep\SleepDailyEntryRepository;
use App\UseCase\Player\Tracking\Sleep\LogSleepUseCase;
use App\UseCase\Player\Tracking\Sleep\UpdateSleepUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateSleepUseCaseTest extends KernelTestCase
{
    public function testItUpdatesTimesAndRecomputesTheDuration(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-sleep-happy');
        $logged = self::logSleep($container, $player, '2026-05-06T23:00:00Z', '2026-05-07T07:00:00Z', 3);

        $output = self::buildUseCase($container, $player)->execute(new UpdateSleepDataInput(
            $logged->id,
            new \DateTimeImmutable('2026-05-06T22:00:00Z'),
            new \DateTimeImmutable('2026-05-07T06:30:00Z'),
            5,
        ));

        self::assertSame($logged->id, $output->id);
        self::assertSame(510, $output->durationMinutes);
        self::assertSame(5, $output->quality);
    }

    public function testItThrowsWhenTheEntryBelongsToAnotherPlayer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $owner = self::createTestPlayer($container, 'update-sleep-owner');
        $intruder = self::createTestPlayer($container, 'update-sleep-intruder');
        $logged = self::logSleep($container, $owner, '2026-05-06T23:00:00Z', '2026-05-07T07:00:00Z', 3);

        $this->expectException(EntityNotFoundException::class);

        self::buildUseCase($container, $intruder)->execute(new UpdateSleepDataInput(
            $logged->id,
            new \DateTimeImmutable('2026-05-06T22:00:00Z'),
            new \DateTimeImmutable('2026-05-07T06:00:00Z'),
            4,
        ));
    }

    public function testItRejectsWakeBeforeBed(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-sleep-reversed');
        $logged = self::logSleep($container, $player, '2026-05-06T23:00:00Z', '2026-05-07T07:00:00Z', 3);

        try {
            self::buildUseCase($container, $player)->execute(new UpdateSleepDataInput(
                $logged->id,
                new \DateTimeImmutable('2026-05-07T08:00:00Z'),
                new \DateTimeImmutable('2026-05-07T07:00:00Z'),
                3,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateSleepValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('wakeAt', $e->violations);
        }
    }

    private static function logSleep(ContainerInterface $container, PlayerDataModel $player, string $bedAt, string $wakeAt, int $quality): SleepDailyEntryDataOutput
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

        return $logUseCase->execute(new LogSleepDataInput(
            new \DateTimeImmutable($bedAt),
            new \DateTimeImmutable($wakeAt),
            $quality,
        ));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdateSleepUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new SleepDailyEntryRepository($registry);

        return new UpdateSleepUseCase(
            new UpdateSleepValidator($resolver, $repo),
            $resolver,
            $repo,
            new SleepDailyEntryPersister($em, $clock),
            $container->get(QuestProgressionEvaluator::class),
            $clock,
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
