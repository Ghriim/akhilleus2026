<?php

declare(strict_types=1);

namespace App\UseCase\User;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataOutput\User\RegisterPlayerDataOutput;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Validator\User\RegisterPlayerValidator;
use App\UseCase\AbstractPublicUseCase;

final class RegisterPlayerUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly RegisterPlayerValidator $registerPlayerValidator,
        private readonly PlayerPersisterGateway $playerPersister,
    ) {
    }

    /**
     * @param RegisterPlayerDataInput $input
     */
    public function execute(DataInputInterface $input): RegisterPlayerDataOutput
    {
        $this->registerPlayerValidator->validate($input);

        $player = $this->playerPersister->create($input);

        return new RegisterPlayerDataOutput(
            $player->id,
            $player->user->email,
            $player->displayName,
        );
    }
}
