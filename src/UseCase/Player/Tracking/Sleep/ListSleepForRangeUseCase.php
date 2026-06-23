<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\ListSleepForRangeDataInput;
use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\SleepDailyEntryDataOutput;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Sleep\ListSleepForRangeValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListSleepForRangeUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly ListSleepForRangeValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly SleepDailyEntryProviderGateway $sleepProvider,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListSleepForRangeDataInput $input
     *
     * @return list<SleepDailyEntryDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $entries = $this->sleepProvider->findAllByPlayerForRange(
            $player,
            $input->from->setTime(0, 0, 0),
            $input->to->setTime(0, 0, 0),
        );

        return array_map(
            fn (SleepDailyEntryDataModel $entry): SleepDailyEntryDataOutput => $this->mapper->map($entry, SleepDailyEntryDataOutput::class),
            $entries,
        );
    }
}
