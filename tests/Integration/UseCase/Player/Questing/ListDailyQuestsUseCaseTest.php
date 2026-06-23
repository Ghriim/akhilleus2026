<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\Player\Questing\ListQuestsDataInput;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Service\Questing\QuestProgressionFactory;
use App\UseCase\Player\Questing\ListDailyQuestsUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListDailyQuestsUseCaseTest extends KernelTestCase
{
    use QuestingPlayerTestTrait;

    public function testItListsActiveDailyQuestsAndFindOrCreatesTheirProgression(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-daily-quests');
        $daily = self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::DAILY);
        self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::WEEKLY);

        $useCase = new ListDailyQuestsUseCase(
            self::stubResolver($player),
            $container->get(QuestProviderGateway::class),
            $container->get(QuestProgressionFactory::class),
            $container->get(ClockInterface::class),
            self::getContainer()->get(ObjectMapperInterface::class),
        );

        $output = $useCase->execute(new ListQuestsDataInput());

        $byQuestId = [];
        foreach ($output as $item) {
            $byQuestId[$item->questId] = $item;
        }

        self::assertArrayHasKey($daily->id, $byQuestId, 'the daily quest is listed');
        self::assertSame(QuestPeriodicityRegistry::DAILY, $byQuestId[$daily->id]->periodicity);
        // A MANUAL quest is CLAIMABLE from the start.
        self::assertSame(QuestProgressionStatusRegistry::CLAIMABLE, $byQuestId[$daily->id]->status);
        self::assertNotEmpty($byQuestId[$daily->id]->id, 'progression was find-or-created');
    }
}
