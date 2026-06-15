<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\GetTodayStepsDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use App\Infrastructure\Persister\Tracking\Steps\StepsDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Steps\StepsDailyEntryRepository;
use App\UseCase\Player\Tracking\Steps\GetTodayStepsUseCase;
use App\UseCase\Player\Tracking\Steps\UpsertStepsForDayUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetTodayStepsUseCaseTest extends KernelTestCase
{
    public function testItLazyCreatesTodaysEntryWithThePlayerDefaultTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);

        $player = self::createTestPlayer($container, 'get-steps-lazy');
        $output = self::buildUseCase($container, $player)->execute(new GetTodayStepsDataInput());

        self::assertSame(0, $output->count);
        self::assertSame(5000, $output->target);
        self::assertSame($clock->now()->setTime(0, 0, 0)->format(\DateTimeInterface::ATOM), $output->date);

        $repo = new StepsDailyEntryRepository($container->get(ManagerRegistry::class));
        self::assertNotNull($repo->findOneByPlayerAndDate($player, $clock->now()));
    }

    public function testItReturnsTheExistingEntryWithItsCountAndTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);

        $player = self::createTestPlayer($container, 'get-steps-existing');
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $repo = new StepsDailyEntryRepository($registry);
        $persister = new StepsDailyEntryPersister($em, $clock);

        $upsert = new UpsertStepsForDayUseCase(new UpsertStepsForDayValidator($resolver), $resolver, $repo, $persister, $container->get(QuestProgressionEvaluator::class), $clock);
        $upsert->execute(new UpsertStepsForDayDataInput($clock->now(), 7200));

        $useCase = new GetTodayStepsUseCase($resolver, $repo, $persister, $clock);
        $output = $useCase->execute(new GetTodayStepsDataInput());

        self::assertSame(7200, $output->count);
        self::assertSame(5000, $output->target);
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): GetTodayStepsUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new GetTodayStepsUseCase(
            $resolver,
            new StepsDailyEntryRepository($registry),
            new StepsDailyEntryPersister($em, $clock),
            $clock,
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
