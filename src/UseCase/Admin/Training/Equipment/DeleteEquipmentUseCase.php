<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\DeleteEquipmentDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\DeleteEquipmentDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Equipment\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\UseCase\AbstractLoggedAdminUseCase;

final class DeleteEquipmentUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly EquipmentProviderGateway $equipmentProvider,
        private readonly EquipmentPersisterGateway $equipmentPersister,
    ) {
    }

    /**
     * @param DeleteEquipmentDataInput $input
     */
    public function execute(DataInputInterface $input): DeleteEquipmentDataOutput
    {
        $equipment = $this->equipmentProvider->findOneForAdminDetails($input->id);
        if (null === $equipment) {
            throw new EntityNotFoundException(sprintf('Equipment "%s" not found.', $input->id));
        }

        $this->equipmentPersister->delete($equipment);

        return new DeleteEquipmentDataOutput($input->id);
    }
}
