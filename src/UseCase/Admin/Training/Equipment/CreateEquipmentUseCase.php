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
    }

    /**
     * @param CreateEquipmentDataInput $input
     */
    public function execute(DataInputInterface $input): EquipmentDataOutput
    {
        $this->createEquipmentValidator->validate($input);

        $equipment = $this->equipmentPersister->create(new EquipmentDataModel($input->label));

        return new EquipmentDataOutput($equipment->id, $equipment->slug, $equipment->label);
    }
}
