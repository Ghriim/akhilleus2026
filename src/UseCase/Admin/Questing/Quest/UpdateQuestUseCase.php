<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\UpdateQuestDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Questing\Quest\QuestDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Validator\Admin\Questing\Quest\UpdateQuestValidator;
use App\UseCase\AbstractLoggedAdminUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateQuestUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly UpdateQuestValidator $updateQuestValidator,
        private readonly QuestProviderGateway $questProvider,
        private readonly QuestPersisterGateway $questPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateQuestDataInput $input
     */
    public function execute(DataInputInterface $input): QuestDataOutput
    {
        $quest = $this->questProvider->findOneByIdForAdminAction($input->id);
        if (null === $quest) {
            throw new EntityNotFoundException(sprintf('Quest "%s" not found.', $input->id));
        }

        $this->updateQuestValidator->validate($input);

        // In-flight QuestProgression rows are intentionally NOT recomputed here (out of v1 scope).
        $quest->label = $input->label;
        $quest->kind = $input->kind;
        $quest->metric = $input->metric;
        $quest->periodicity = $input->periodicity;
        $quest->targetValue = $input->targetValue;
        $quest->rewardedXp = $input->rewardedXp;
        $quest->dateStart = $input->dateStart;
        $quest->dateEnd = $input->dateEnd;
        $this->questPersister->update($quest);

        return $this->mapper->map($quest, QuestDataOutput::class);
    }
}
