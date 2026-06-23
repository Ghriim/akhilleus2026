<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\GetEquipmentDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GetEquipmentDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private EquipmentProviderGateway $equipmentProvider,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param GetEquipmentDetailsDataInput $input
     */
    public function execute(DataInputInterface $input): EquipmentDataOutput
    {
        $equipment = $this->equipmentProvider->findOneForAdminDetails($input->id);
        if (null === $equipment) {
            throw new EntityNotFoundException(sprintf('Equipment "%s" not found.', $input->id));
        }

        return $this->mapper->map($equipment, EquipmentDataOutput::class);
    }
}
