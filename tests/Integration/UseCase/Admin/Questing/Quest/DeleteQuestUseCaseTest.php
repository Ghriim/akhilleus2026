<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\DeleteQuestDataInput;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\UseCase\Admin\Questing\Quest\DeleteQuestUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteQuestUseCaseTest extends KernelTestCase
{
    public function testItDeletesAQuest(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $quest = $container->get(QuestPersisterGateway::class)->create(new QuestDataModel(
            'To delete',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::WEEKLY,
            new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
            50,
        ));

        $useCase = $container->get(DeleteQuestUseCase::class);
        $output = $useCase->execute(new DeleteQuestDataInput($quest->id));

        self::assertSame($quest->id, $output->deletedId);
        self::assertNull($container->get(QuestProviderGateway::class)->findOneByIdForAdminAction($quest->id));
    }

    public function testItThrowsWhenTheQuestDoesNotExist(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $useCase = $container->get(DeleteQuestUseCase::class);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new DeleteQuestDataInput('01HZX000000000000000000404'));
    }
}
