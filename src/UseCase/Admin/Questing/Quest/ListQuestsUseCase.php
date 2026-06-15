<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\ListQuestsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataOutput\Admin\Questing\Quest\QuestListItemDataOutput;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\UseCase\AbstractPublicUseCase;

final class ListQuestsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly QuestProviderGateway $questProvider,
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
            static fn (QuestDataModel $quest): QuestListItemDataOutput => new QuestListItemDataOutput(
                $quest->id,
                $quest->label,
                $quest->kind,
                $quest->metric,
                $quest->periodicity,
                $quest->targetValue,
                $quest->rewardedXp,
                $quest->dateStart->format(\DateTimeInterface::ATOM),
                $quest->dateEnd?->format(\DateTimeInterface::ATOM),
            ),
            $this->questProvider->findAllForAdminList(),
        );
    }
}
