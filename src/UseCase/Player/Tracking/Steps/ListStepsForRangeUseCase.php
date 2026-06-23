<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\ListStepsForRangeDataInput;
use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\StepsDailyEntryDataOutput;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\ListStepsForRangeValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListStepsForRangeUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly ListStepsForRangeValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly StepsDailyEntryProviderGateway $stepsProvider,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListStepsForRangeDataInput $input
     *
     * @return list<StepsDailyEntryDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $entries = $this->stepsProvider->findAllByPlayerForRange(
            $player,
            $input->from->setTime(0, 0, 0),
            $input->to->setTime(0, 0, 0),
        );

        return array_map(
            fn (StepsDailyEntryDataModel $entry): StepsDailyEntryDataOutput => $this->mapper->map($entry, StepsDailyEntryDataOutput::class),
            $entries,
        );
    }
}
