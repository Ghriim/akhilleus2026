<?php

declare(strict_types=1);

namespace App\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Questing\ClaimQuestRewardDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataOutput\Player\Questing\ClaimQuestRewardDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Domain\Gateway\Provider\Questing\QuestProgression\QuestProgressionProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Questing\ClaimQuestRewardValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class ClaimQuestRewardUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly ClaimQuestRewardValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly QuestProgressionProviderGateway $progressionProvider,
        private readonly QuestProgressionPersisterGateway $progressionPersister,
        private readonly EarnedExperiencePersisterGateway $earnedExperiencePersister,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param ClaimQuestRewardDataInput $input
     */
    public function execute(DataInputInterface $input): ClaimQuestRewardDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $progression = $this->progressionProvider->findOneByIdForPlayerAction($input->progressionId, $player);
        if (null === $progression) {
            throw new EntityNotFoundException(sprintf('No quest progression "%s" for this player.', $input->progressionId));
        }

        $now = $this->clock->now();
        $this->validator->validate($progression, $now);

        $progression->status = QuestProgressionStatusRegistry::REWARDED;
        $progression->claimedDate = $now;
        $this->progressionPersister->update($progression);

        $earned = new EarnedExperienceDataModel(
            $player,
            'Quest: '.$progression->quest->label,
            $progression->quest->rewardedXp,
            $now,
            EarnedExperienceSourceTypeRegistry::QUEST,
            $progression->id,
        );
        $this->earnedExperiencePersister->create($earned);

        return new ClaimQuestRewardDataOutput($progression->id, $earned->id, $earned->amount);
    }
}
