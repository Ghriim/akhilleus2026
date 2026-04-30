<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\DeleteEquipmentDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\DeleteEquipmentDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Equipment\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Validator\Admin\Training\Equipment\DeleteEquipmentValidator;
use App\UseCase\AbstractLoggedUserUseCase;

final class DeleteEquipmentUseCase extends AbstractLoggedUserUseCase
{
    public function __construct(
        private readonly DeleteEquipmentValidator $deleteEquipmentValidator,
        private readonly EquipmentProviderGateway $equipmentProvider,
        private readonly EquipmentPersisterGateway $equipmentPersister,
    ) {
        parent::__construct($deleteEquipmentValidator);
    }

    public function execute(DeleteEquipmentDataInput|DataInputInterface $input): DeleteEquipmentDataOutput
    {
        if (false === $input instanceof DeleteEquipmentDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeleteEquipmentDataInput::class, $input::class));
        }

        $this->deleteEquipmentValidator->validate($input);

        $equipment = $this->equipmentProvider->findOneForAdminDetails($input->id);
        if (null === $equipment) {
            throw new EntityNotFoundException(sprintf('Equipment "%s" not found.', $input->id));
        }

        $this->equipmentPersister->delete($equipment);

        return new DeleteEquipmentDataOutput($input->id);
    }
}
