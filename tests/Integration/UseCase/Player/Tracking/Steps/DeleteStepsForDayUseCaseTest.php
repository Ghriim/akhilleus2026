<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\DeleteStepsForDayDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use App\Infrastructure\Persister\Tracking\Steps\StepsDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Steps\StepsDailyEntryRepository;
use App\UseCase\Player\Tracking\Steps\DeleteStepsForDayUseCase;
use App\UseCase\Player\Tracking\Steps\UpsertStepsForDayUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteStepsForDayUseCaseTest extends KernelTestCase
{
    public function testItDeletesTheStepsEntryForTheDay(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'delete-steps-happy');
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);
        $repo = new StepsDailyEntryRepository($registry);
        $persister = new StepsDailyEntryPersister($em, $clock);

        $upsert = new UpsertStepsForDayUseCase(
            new UpsertStepsForDayValidator($resolver),
            $resolver,
            $repo,
            $persister,
        );
        $upsert->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-07'), 5000));

        $delete = new DeleteStepsForDayUseCase($resolver, $repo, $persister);
        $output = $delete->execute(new DeleteStepsForDayDataInput(new \DateTimeImmutable('2026-05-07')));

        self::assertSame(
            (new \DateTimeImmutable('2026-05-07'))->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM),
            $output->deletedDate,
        );
        self::assertNull($repo->findOneByPlayerAndDate($player, new \DateTimeImmutable('2026-05-07')));
    }

    public function testItThrowsWhenNoEntryExistsForTheDay(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'delete-steps-missing');
        $useCase = self::buildUseCase($container, $player);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new DeleteStepsForDayDataInput(new \DateTimeImmutable('2026-05-07')));
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): DeleteStepsForDayUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new DeleteStepsForDayUseCase(
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
