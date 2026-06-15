<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\ListQuestsDataInput;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\UseCase\Admin\Questing\Quest\ListQuestsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListQuestsUseCaseTest extends KernelTestCase
{
    public function testItListsEveryQuestIncludingTheSeededOnes(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $persister = $container->get(QuestPersisterGateway::class);

        $first = $persister->create(new QuestDataModel(
            'Older quest',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::WEEKLY,
            new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            50,
        ));
        $second = $persister->create(new QuestDataModel(
            'Newer quest',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::WEEKLY,
            new \DateTimeImmutable('2026-05-01T00:00:00+00:00'),
            50,
        ));

        $useCase = $container->get(ListQuestsUseCase::class);
        $output = $useCase->execute(new ListQuestsDataInput());

        $ids = array_map(static fn ($item): string => $item->id, $output);
        self::assertContains($first->id, $ids);
        self::assertContains($second->id, $ids);

        // Ordered by dateStart DESC: the newer quest must appear before the older one.
        self::assertLessThan(
            array_search($first->id, $ids, true),
            array_search($second->id, $ids, true),
        );
    }
}
