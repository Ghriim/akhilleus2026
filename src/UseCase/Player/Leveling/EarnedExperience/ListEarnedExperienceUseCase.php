<?php

declare(strict_types=1);

namespace App\UseCase\Player\Leveling\EarnedExperience;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Leveling\EarnedExperience\ListEarnedExperienceDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataOutput\Player\Leveling\EarnedExperience\EarnedExperienceDataOutput;
use App\Domain\DTO\DataOutput\Player\Leveling\EarnedExperience\EarnedExperienceJournalDataOutput;
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Leveling\EarnedExperience\ListEarnedExperienceValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class ListEarnedExperienceUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly ListEarnedExperienceValidator $listEarnedExperienceValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly EarnedExperienceProviderGateway $earnedExperienceProvider,
    ) {
    }

    /**
     * @param ListEarnedExperienceDataInput $input
     */
    public function execute(DataInputInterface $input): EarnedExperienceJournalDataOutput
    {
        $this->listEarnedExperienceValidator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $entries = $this->earnedExperienceProvider->findAllByPlayerForJournal($player, $input->page, $input->perPage);
        $totalCount = $this->earnedExperienceProvider->countByPlayerForJournal($player);

        $items = array_map(
            static fn (EarnedExperienceDataModel $entry): EarnedExperienceDataOutput => new EarnedExperienceDataOutput(
                $entry->id,
                $entry->label,
                $entry->amount,
                $entry->earnedAt->format(\DateTimeInterface::ATOM),
                $entry->sourceType,
                $entry->sourceId,
                $entry->isLocked,
            ),
            $entries,
        );

        return new EarnedExperienceJournalDataOutput($items, $input->page, $input->perPage, $totalCount);
    }
}
