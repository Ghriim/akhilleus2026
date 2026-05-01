<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\GetMovementDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentListItemDataOutput;
use App\Domain\DTO\DataOutput\Admin\Training\Movement\MovementDataOutput;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleListItemDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\UseCase\AbstractPublicUseCase;

final class GetMovementDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly MovementProviderGateway $movementProvider,
    ) {
    }

    /**
     * @param GetMovementDetailsDataInput $input
     */
    public function execute(DataInputInterface $input): MovementDataOutput
    {
        $movement = $this->movementProvider->findOneForAdminDetails($input->id);
        if (null === $movement) {
            throw new EntityNotFoundException(sprintf('Movement "%s" not found.', $input->id));
        }

        $secondary = [];
        foreach ($movement->secondaryMuscles as $muscle) {
            $secondary[] = new MuscleListItemDataOutput($muscle->id, $muscle->slug, $muscle->label);
        }
        $equipments = [];
        foreach ($movement->equipments as $equipment) {
            $equipments[] = new EquipmentListItemDataOutput($equipment->id, $equipment->slug, $equipment->label);
        }

        return new MovementDataOutput(
            $movement->id,
            $movement->slug,
            $movement->label,
            new MuscleListItemDataOutput($movement->mainMuscle->id, $movement->mainMuscle->slug, $movement->mainMuscle->label),
            $secondary,
            $equipments,
            $movement->tracksRepetitions,
            $movement->tracksWeight,
            $movement->tracksDuration,
            $movement->tracksDistance,
            $movement->tracksInclinePercent,
            $movement->tracksInclineMeters,
        );
    }
}
