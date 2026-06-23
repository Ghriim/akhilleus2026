<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Movement;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Movement\ListMovementsForPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Movement\PlayerMovementListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListMovementsForPlayerUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly MovementProviderGateway $movementProvider,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListMovementsForPlayerDataInput $input
     *
     * @return list<PlayerMovementListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        unset($input);

        return array_map(
            fn (MovementDataModel $movement): PlayerMovementListItemDataOutput => $this->mapper->map($movement, PlayerMovementListItemDataOutput::class),
            $this->movementProvider->findAllForAdminList(),
        );
    }
}
