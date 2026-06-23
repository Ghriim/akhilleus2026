<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataModel\Tracking\Weight\WeightEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Weight\WeightEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Weight\WeightEntryPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\LogWeightValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class LogWeightUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LogWeightValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WeightEntryPersisterGateway $weightPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param LogWeightDataInput $input
     */
    public function execute(DataInputInterface $input): WeightEntryDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $this->validator->validate($player, $input);

        $entry = new WeightEntryDataModel($player, $input->loggedAt, $input->valueGrams);
        $this->weightPersister->create($entry);

        return $this->mapper->map($entry, WeightEntryDataOutput::class);
    }
}
