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
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateEquipmentUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly UpdateEquipmentValidator $updateEquipmentValidator,
        private readonly EquipmentProviderGateway $equipmentProvider,
        private readonly EquipmentPersisterGateway $equipmentPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateEquipmentDataInput $input
     */
    public function execute(DataInputInterface $input): EquipmentDataOutput
    {
        $equipment = $this->equipmentProvider->findOneForAdminDetails($input->id);
        if (null === $equipment) {
            throw new EntityNotFoundException(sprintf('Equipment "%s" not found.', $input->id));
        }

        $this->updateEquipmentValidator->validate($input, $equipment);

        $equipment->label = $input->label;
        $this->equipmentPersister->update($equipment);

        return $this->mapper->map($equipment, EquipmentDataOutput::class);
    }
}
