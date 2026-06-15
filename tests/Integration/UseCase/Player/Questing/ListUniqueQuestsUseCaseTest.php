<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\Player\Questing\ListQuestsDataInput;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Service\Questing\QuestProgressionFactory;
use App\UseCase\Player\Questing\ListUniqueQuestsUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListUniqueQuestsUseCaseTest extends KernelTestCase
{
    use QuestingPlayerTestTrait;

    public function testItListsActiveUniqueQuestsWithNullPeriodWindow(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $player = self::createTestPlayer($container, 'list-unique-quests');
        $unique = self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::UNIQUE);
        self::seedQuest($container, QuestKindRegistry::MANUAL, QuestPeriodicityRegistry::DAILY);

        $useCase = new ListUniqueQuestsUseCase(
            self::stubResolver($player),
            $container->get(QuestProviderGateway::class),
            $container->get(QuestProgressionFactory::class),
            $container->get(ClockInterface::class),
        );

        $output = $useCase->execute(new ListQuestsDataInput());

        $byQuestId = [];
        foreach ($output as $item) {
            $byQuestId[$item->questId] = $item;
            self::assertSame(QuestPeriodicityRegistry::UNIQUE, $item->periodicity);
        }

        self::assertArrayHasKey($unique->id, $byQuestId);
        // UNIQUE quests carry no period window.
        self::assertNull($byQuestId[$unique->id]->startDate);
        self::assertNull($byQuestId[$unique->id]->endDate);
    }
}
