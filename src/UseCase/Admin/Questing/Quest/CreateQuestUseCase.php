<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\CreateQuestDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataOutput\Admin\Questing\Quest\QuestDataOutput;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Validator\Admin\Questing\Quest\CreateQuestValidator;
use App\UseCase\AbstractLoggedAdminUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class CreateQuestUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly CreateQuestValidator $createQuestValidator,
        private readonly QuestPersisterGateway $questPersister,
        private readonly ClockInterface $clock,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param CreateQuestDataInput $input
     */
    public function execute(DataInputInterface $input): QuestDataOutput
    {
        $this->createQuestValidator->validate($input);

        $quest = $this->questPersister->create(new QuestDataModel(
            $input->label,
            $input->kind,
            $input->periodicity,
            $input->dateStart ?? $this->clock->now(),
            $input->rewardedXp,
            $input->metric,
            $input->targetValue,
            $input->dateEnd,
        ));

        return $this->mapper->map($quest, QuestDataOutput::class);
    }
}
