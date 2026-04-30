<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentDataOutput;
use App\Domain\Gateway\Persister\Training\Equipment\EquipmentPersisterGateway;
use App\Domain\Validator\Admin\Training\Equipment\CreateEquipmentValidator;
use App\UseCase\AbstractLoggedAdminUseCase;

final class CreateEquipmentUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly CreateEquipmentValidator $createEquipmentValidator,
        private readonly EquipmentPersisterGateway $equipmentPersister,
    ) {
        parent::__construct($createEquipmentValidator);
    }

    public function execute(CreateEquipmentDataInput|DataInputInterface $input): EquipmentDataOutput
    {
        if (false === $input instanceof CreateEquipmentDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', CreateEquipmentDataInput::class, $input::class));
        }

        $this->createEquipmentValidator->validate($input);

        $equipment = $this->equipmentPersister->create(new EquipmentDataModel($input->label));

        return new EquipmentDataOutput($equipment->id, $equipment->slug, $equipment->label);
    }
}
