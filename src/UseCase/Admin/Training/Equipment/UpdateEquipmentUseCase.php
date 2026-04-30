<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\UpdateEquipmentDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Equipment\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Validator\Admin\Training\Equipment\UpdateEquipmentValidator;
use App\UseCase\AbstractLoggedAdminUseCase;

final class UpdateEquipmentUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly UpdateEquipmentValidator $updateEquipmentValidator,
        private readonly EquipmentProviderGateway $equipmentProvider,
        private readonly EquipmentPersisterGateway $equipmentPersister,
    ) {
        parent::__construct($updateEquipmentValidator);
    }

    public function execute(UpdateEquipmentDataInput|DataInputInterface $input): EquipmentDataOutput
    {
        if (false === $input instanceof UpdateEquipmentDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', UpdateEquipmentDataInput::class, $input::class));
        }

        $this->updateEquipmentValidator->validate($input);

        $equipment = $this->equipmentProvider->findOneForAdminDetails($input->id);
        if (null === $equipment) {
            throw new EntityNotFoundException(sprintf('Equipment "%s" not found.', $input->id));
        }

        $equipment->label = $input->label;
        $this->equipmentPersister->update($equipment);

        return new EquipmentDataOutput($equipment->id, $equipment->slug, $equipment->label);
    }
}
