<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\Player\Questing\ListQuestsDataInput;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Service\Questing\QuestProgressionFactory;
use App\UseCase\Player\Questing\ListMonthlyQuestsUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListMonthlyQuestsUseCaseTest extends KernelTestCase
{
    use QuestingPlayerTestTrait;

    public function testItListsActiveMonthlyQuestsOnly(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-monthly-quests');
        $monthly = self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::MONTHLY);
        self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::DAILY);

        $useCase = new ListMonthlyQuestsUseCase(
            self::stubResolver($player),
            $container->get(QuestProviderGateway::class),
            $container->get(QuestProgressionFactory::class),
            $container->get(ClockInterface::class),
            self::getContainer()->get(ObjectMapperInterface::class),
        );

        $output = $useCase->execute(new ListQuestsDataInput());

        $questIds = array_map(static fn ($item): string => $item->questId, $output);
        self::assertContains($monthly->id, $questIds);
        foreach ($output as $item) {
            self::assertSame(QuestPeriodicityRegistry::MONTHLY, $item->periodicity);
        }
    }
}
