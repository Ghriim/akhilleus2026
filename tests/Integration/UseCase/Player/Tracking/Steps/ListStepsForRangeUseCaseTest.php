<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\ListStepsForRangeDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Steps\ListStepsForRangeValidator;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use App\Infrastructure\Persister\Tracking\Steps\StepsDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Steps\StepsDailyEntryRepository;
use App\UseCase\Player\Tracking\Steps\ListStepsForRangeUseCase;
use App\UseCase\Player\Tracking\Steps\UpsertStepsForDayUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListStepsForRangeUseCaseTest extends KernelTestCase
{
    public function testItListsEntriesWithinTheInclusiveRangeOrderedByDate(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-steps-range');
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
            $container->get(QuestProgressionEvaluator::class),
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
        );
        $upsert->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-08'), 8000));
        $upsert->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-05'), 5000));
        $upsert->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-05-07'), 7000));
        $upsert->execute(new UpsertStepsForDayDataInput(new \DateTimeImmutable('2026-04-30'), 1000));

        $useCase = new ListStepsForRangeUseCase(new ListStepsForRangeValidator(), $resolver, $repo, self::getContainer()->get(ObjectMapperInterface::class));

        $output = $useCase->execute(new ListStepsForRangeDataInput(
            new \DateTimeImmutable('2026-05-05'),
            new \DateTimeImmutable('2026-05-08'),
        ));

        self::assertCount(3, $output);
        self::assertSame(5000, $output[0]->count);
        self::assertSame(7000, $output[1]->count);
        self::assertSame(8000, $output[2]->count);
    }

    public function testItReturnsAnEmptyListWhenNoEntriesMatch(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-steps-empty');
        $useCase = self::buildUseCase($container, $player);

        $output = $useCase->execute(new ListStepsForRangeDataInput(
            new \DateTimeImmutable('2026-05-01'),
            new \DateTimeImmutable('2026-05-07'),
        ));

        self::assertSame([], $output);
    }

    public function testItRejectsAReversedRange(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-steps-reversed');
        $useCase = self::buildUseCase($container, $player);

        try {
            $useCase->execute(new ListStepsForRangeDataInput(
                new \DateTimeImmutable('2026-05-07'),
                new \DateTimeImmutable('2026-05-01'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ListStepsForRangeValidator::ERROR_CODE, $e->errorCode);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): ListStepsForRangeUseCase
    {
        $resolver = self::stubResolver($player);
        $registry = $container->get(ManagerRegistry::class);

        return new ListStepsForRangeUseCase(
            new ListStepsForRangeValidator(),
            $resolver,
            new StepsDailyEntryRepository($registry),
            self::getContainer()->get(ObjectMapperInterface::class),
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
