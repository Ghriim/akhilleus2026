<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use App\Infrastructure\Persister\Tracking\Steps\StepsDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Steps\StepsDailyEntryRepository;
use App\UseCase\Player\Tracking\Steps\UpsertStepsForDayUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpsertStepsForDayUseCaseTest extends KernelTestCase
{
    public function testItCreatesANewStepsEntryForTheDay(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'upsert-steps-create');
        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-07T10:30:00Z'), 8500));

        self::assertNotEmpty($output->id);
        self::assertSame(8500, $output->count);
        self::assertSame(5000, $output->target, 'target is snapshotted from the player default at create');
        self::assertSame((new \DateTimeImmutable('2026-05-07'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM), $output->date);

        $repo = new StepsDailyEntryRepository($container->get(ManagerRegistry::class));
        $persisted = $repo->findOneByPlayerAndDate($player, new \DateTimeImmutable('2026-05-07'));
        self::assertNotNull($persisted);
        self::assertSame(8500, $persisted->count);
        self::assertSame(5000, $persisted->target);
    }

    public function testItUpdatesAnExistingEntryForTheSameDay(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'upsert-steps-update');
        $useCase = self::buildUseCase($container, $player);

        $first = $useCase->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-08'), 4000));
        $second = $useCase->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-08'), 9200));

        self::assertSame($first->id, $second->id);
        self::assertSame(9200, $second->count);

        $repo = new StepsDailyEntryRepository($container->get(ManagerRegistry::class));
        $persisted = $repo->findOneByPlayerAndDate($player, new \DateTimeImmutable('2026-05-08'));
        self::assertNotNull($persisted);
        self::assertSame(9200, $persisted->count);
    }

    public function testItRejectsANegativeCount(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'upsert-steps-negative');
        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-07'), -1));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpsertStepsForDayValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('count', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpsertStepsForDayUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new UpsertStepsForDayUseCase(
            new UpsertStepsForDayValidator($resolver),
            $resolver,
            new StepsDailyEntryRepository($registry),
            new StepsDailyEntryPersister($em, $clock),
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
