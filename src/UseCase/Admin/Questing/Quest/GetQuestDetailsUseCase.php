<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\GetQuestDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Questing\Quest\QuestDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\UseCase\AbstractPublicUseCase;

final class GetQuestDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly QuestProviderGateway $questProvider,
    ) {
    }

    /**
     * @param GetQuestDetailsDataInput $input
     */
    public function execute(DataInputInterface $input): QuestDataOutput
    {
        $quest = $this->questProvider->findOneByIdForAdminAction($input->id);
        if (null === $quest) {
            throw new EntityNotFoundException(sprintf('Quest "%s" not found.', $input->id));
        }

        return new QuestDataOutput(
            $quest->id,
            $quest->label,
            $quest->kind,
            $quest->metric,
            $quest->periodicity,
            $quest->targetValue,
            $quest->rewardedXp,
            $quest->dateStart->format(\DateTimeInterface::ATOM),
            $quest->dateEnd?->format(\DateTimeInterface::ATOM),
        );
    }
}
