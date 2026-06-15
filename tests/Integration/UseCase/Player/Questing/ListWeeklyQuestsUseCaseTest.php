<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\Player\Questing\ListQuestsDataInput;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Service\Questing\QuestProgressionFactory;
use App\UseCase\Player\Questing\ListWeeklyQuestsUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListWeeklyQuestsUseCaseTest extends KernelTestCase
{
    use QuestingPlayerTestTrait;

    public function testItListsActiveWeeklyQuestsOnly(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-weekly-quests');
        $weekly = self::seedQuest(
            $container,
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::WEEKLY,
            QuestMetricRegistry::WORKOUT_COUNT,
            '3',
        );
        self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::DAILY);

        $useCase = new ListWeeklyQuestsUseCase(
            self::stubResolver($player),
            $container->get(QuestProviderGateway::class),
            $container->get(QuestProgressionFactory::class),
            $container->get(ClockInterface::class),
        );

        $output = $useCase->execute(new ListQuestsDataInput());

        $byQuestId = [];
        foreach ($output as $item) {
            $byQuestId[$item->questId] = $item;
            self::assertSame(QuestPeriodicityRegistry::WEEKLY, $item->periodicity, 'only weekly quests are listed');
        }

        self::assertArrayHasKey($weekly->id, $byQuestId);
        // An AUTOMATIC quest starts IN_PROGRESS with a zeroed current value.
        self::assertSame(QuestProgressionStatusRegistry::IN_PROGRESS, $byQuestId[$weekly->id]->status);
        self::assertSame('0', $byQuestId[$weekly->id]->currentValue, 'freshly created automatic progression starts at 0');
        self::assertSame('3', $byQuestId[$weekly->id]->targetValue);
    }
}
