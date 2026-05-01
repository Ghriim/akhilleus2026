<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Movement;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Movement\ListMovementsForPlayerDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Movement\PlayerMovementListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class ListMovementsForPlayerUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly MovementProviderGateway $movementProvider,
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
            static fn ($movement) => new PlayerMovementListItemDataOutput(
                $movement->id,
                $movement->slug,
                $movement->label,
                $movement->mainMuscle->slug,
                $movement->tracksRepetitions,
                $movement->tracksWeight,
                $movement->tracksDuration,
                $movement->tracksDistance,
                $movement->tracksInclinePercent,
                $movement->tracksInclineMeters,
            ),
            $this->movementProvider->findAllForAdminList(),
        );
    }
}
