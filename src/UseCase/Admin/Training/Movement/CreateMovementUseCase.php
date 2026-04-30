<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentListItemDataOutput;
use App\Domain\DTO\DataOutput\Admin\Training\Movement\MovementDataOutput;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleListItemDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Validator\Admin\Training\Movement\CreateMovementValidator;
use App\UseCase\AbstractLoggedAdminUseCase;

final class CreateMovementUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly CreateMovementValidator $createMovementValidator,
        private readonly MovementPersisterGateway $movementPersister,
        private readonly MuscleProviderGateway $muscleProvider,
        private readonly EquipmentProviderGateway $equipmentProvider,
    ) {
        parent::__construct($createMovementValidator);
    }

    public function execute(CreateMovementDataInput|DataInputInterface $input): MovementDataOutput
    {
        if (false === $input instanceof CreateMovementDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', CreateMovementDataInput::class, $input::class));
        }

        $this->createMovementValidator->validate($input);

        $mainMuscle = $this->muscleProvider->findOneForAdminDetails($input->mainMuscleId);
        if (null === $mainMuscle) {
            throw new EntityNotFoundException(sprintf('Muscle "%s" not found.', $input->mainMuscleId));
        }

        $movement = new MovementDataModel($input->label, $mainMuscle);
        foreach ($input->secondaryMuscleIds as $secondaryId) {
            $secondary = $this->muscleProvider->findOneForAdminDetails($secondaryId);
            if (null !== $secondary) {
                $movement->secondaryMuscles->add($secondary);
            }
        }
        foreach ($input->equipmentIds as $equipmentId) {
            $equipment = $this->equipmentProvider->findOneForAdminDetails($equipmentId);
            if (null !== $equipment) {
                $movement->equipments->add($equipment);
            }
        }
        $movement->tracksRepetitions = $input->tracksRepetitions;
        $movement->tracksWeight = $input->tracksWeight;
        $movement->tracksDuration = $input->tracksDuration;
        $movement->tracksDistance = $input->tracksDistance;
        $movement->tracksInclinePercent = $input->tracksInclinePercent;
        $movement->tracksInclineMeters = $input->tracksInclineMeters;

        $this->movementPersister->create($movement);

        return $this->buildOutput($movement);
    }

    private function buildOutput(MovementDataModel $movement): MovementDataOutput
    {
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
