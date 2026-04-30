<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\GetEquipmentDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Validator\Admin\Training\Equipment\GetEquipmentDetailsValidator;
use App\UseCase\AbstractPublicUseCase;

final class GetEquipmentDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly GetEquipmentDetailsValidator $getEquipmentDetailsValidator,
        private readonly EquipmentProviderGateway $equipmentProvider,
    ) {
        parent::__construct($getEquipmentDetailsValidator);
    }

    public function execute(GetEquipmentDetailsDataInput|DataInputInterface $input): EquipmentDataOutput
    {
        if (false === $input instanceof GetEquipmentDetailsDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', GetEquipmentDetailsDataInput::class, $input::class));
        }

        $this->getEquipmentDetailsValidator->validate($input);

        $equipment = $this->equipmentProvider->findOneForAdminDetails($input->id);
        if (null === $equipment) {
            throw new EntityNotFoundException(sprintf('Equipment "%s" not found.', $input->id));
        }

        return new EquipmentDataOutput($equipment->id, $equipment->slug, $equipment->label);
    }
}
