<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\ListQuestsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataOutput\Admin\Questing\Quest\QuestListItemDataOutput;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ListQuestsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private QuestProviderGateway $questProvider,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListQuestsDataInput $input
     *
     * @return list<QuestListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        return array_map(
            fn (QuestDataModel $quest): QuestListItemDataOutput => $this->mapper->map($quest, QuestListItemDataOutput::class),
            $this->questProvider->findAllForAdminList(),
        );
    }
}
