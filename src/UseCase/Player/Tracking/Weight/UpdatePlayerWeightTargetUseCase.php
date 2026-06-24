<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdatePlayerWeightTargetDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Weight\PlayerWeightTargetDataOutput;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\UpdatePlayerWeightTargetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdatePlayerWeightTargetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdatePlayerWeightTargetValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly PlayerPersisterGateway $playerPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdatePlayerWeightTargetDataInput $input
     */
    public function execute(DataInputInterface $input): PlayerWeightTargetDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $player->targetWeightGrams = $input->targetGrams;
        $this->playerPersister->update($player);

        return $this->mapper->map($player, PlayerWeightTargetDataOutput::class);
    }
}
