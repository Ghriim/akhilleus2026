<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\GetQuestDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Questing\Quest\QuestDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GetQuestDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private QuestProviderGateway $questProvider,
        private ObjectMapperInterface $mapper,
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

        return $this->mapper->map($quest, QuestDataOutput::class);
    }
}
