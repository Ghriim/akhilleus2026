<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\DeleteQuestDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Questing\Quest\QuestPersisterGateway;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\UseCase\AbstractLoggedAdminUseCase;

final class DeleteQuestUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly QuestProviderGateway $questProvider,
        private readonly QuestPersisterGateway $questPersister,
    ) {
    }

    /**
     * @param DeleteQuestDataInput $input
     */
    public function execute(DataInputInterface $input): null
    {
        $quest = $this->questProvider->findOneByIdForAdminAction($input->id);
        if (null === $quest) {
            throw new EntityNotFoundException(sprintf('Quest "%s" not found.', $input->id));
        }

        $this->questPersister->delete($quest);

        return null;
    }
}
