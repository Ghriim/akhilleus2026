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
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GetMovementDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private MovementProviderGateway $movementProvider,
        private ObjectMapperInterface $mapper,
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
            $secondary[] = $this->mapper->map($muscle, MuscleListItemDataOutput::class);
        }
        $equipments = [];
        foreach ($movement->equipments as $equipment) {
            $equipments[] = $this->mapper->map($equipment, EquipmentListItemDataOutput::class);
        }

        return new MovementDataOutput(
            $movement->id,
            $movement->slug,
            $movement->label,
            $this->mapper->map($movement->mainMuscle, MuscleListItemDataOutput::class),
            $secondary,
            $equipments,
            $movement->tracksRepetitions,
            $movement->tracksWeight,
            $movement->tracksDuration,
            $movement->tracksDistance,
            $movement->tracksInclinePercent,
            $movement->tracksInclineMeters,
            $movement->videoLink,
            $movement->gifLink,
        );
    }
}
