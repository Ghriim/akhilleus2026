<?php

declare(strict_types=1);

namespace App\UseCase\User;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataOutput\User\RegisterPlayerDataOutput;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Validator\User\RegisterPlayerValidator;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class RegisterPlayerUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private RegisterPlayerValidator $registerPlayerValidator,
        private PlayerPersisterGateway $playerPersister,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param RegisterPlayerDataInput $input
     */
    public function execute(DataInputInterface $input): RegisterPlayerDataOutput
    {
        $this->registerPlayerValidator->validate($input);

        $player = $this->playerPersister->create($input);

        return $this->mapper->map($player, RegisterPlayerDataOutput::class);
    }
}
