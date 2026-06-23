<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdateStepsDailyTargetDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Steps\UpdateStepsDailyTargetValidator;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use App\Infrastructure\Persister\Tracking\Steps\StepsDailyEntryPersister;
use App\Infrastructure\Repository\Tracking\Steps\StepsDailyEntryRepository;
use App\UseCase\Player\Tracking\Steps\UpdateStepsDailyTargetUseCase;
use App\UseCase\Player\Tracking\Steps\UpsertStepsForDayUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateStepsDailyTargetUseCaseTest extends KernelTestCase
{
    public function testItLazyCreatesTodaysEntryWithTheGivenTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-steps-target-lazy');
        $output = self::buildUseCase($container, $player)->execute(new UpdateStepsDailyTargetDataInput(9000));

        self::assertSame(0, $output->count);
        self::assertSame(9000, $output->target);
    }

    public function testItOverridesTheTargetOnTodaysExistingEntryKeepingTheCount(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $clock = $container->get(ClockInterface::class);

        $player = self::createTestPlayer($container, 'update-steps-target-existing');
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $repo = new StepsDailyEntryRepository($registry);
        $persister = new StepsDailyEntryPersister($em, $clock);

        $upsert = new UpsertStepsForDayUseCase(new UpsertStepsForDayValidator($resolver), $resolver, $repo, $persister, $container->get(QuestProgressionEvaluator::class), $clock, self::getContainer()->get(ObjectMapperInterface::class));
        $upsert->execute(new UpsertStepsForDayDataInput($clock->now(), 4200));

        $useCase = new UpdateStepsDailyTargetUseCase(
            new UpdateStepsDailyTargetValidator($resolver),
            $resolver,
            $repo,
            $persister,
            $clock,
            self::getContainer()->get(ObjectMapperInterface::class),
        );
        $output = $useCase->execute(new UpdateStepsDailyTargetDataInput(12000));

        self::assertSame(4200, $output->count);
        self::assertSame(12000, $output->target);
    }

    public function testItRejectsANonPositiveTarget(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'update-steps-target-invalid');

        try {
            self::buildUseCase($container, $player)->execute(new UpdateStepsDailyTargetDataInput(0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateStepsDailyTargetValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('target', $e->violations);
        }
    }

    private static function buildUseCase(ContainerInterface $container, PlayerDataModel $player): UpdateStepsDailyTargetUseCase
    {
        $resolver = self::stubResolver($player);
        $em = $container->get('doctrine.orm.entity_manager');
        $registry = $container->get(ManagerRegistry::class);
        $clock = $container->get(ClockInterface::class);

        return new UpdateStepsDailyTargetUseCase(
            new UpdateStepsDailyTargetValidator($resolver),
            $resolver,
            new StepsDailyEntryRepository($registry),
            new StepsDailyEntryPersister($em, $clock),
            $clock,
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
