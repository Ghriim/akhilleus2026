<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\ListEquipmentsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Validator\Admin\Training\Equipment\ListEquipmentsValidator;
use App\UseCase\AbstractPublicUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ListEquipmentsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private ListEquipmentsValidator $listEquipmentsValidator,
        private EquipmentProviderGateway $equipmentProvider,
        private ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListEquipmentsDataInput $input
     *
     * @return list<EquipmentListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $this->listEquipmentsValidator->validate($input);

        $equipments = $this->equipmentProvider->findAllForAdminList($input->sort, $input->direction);

        $outputs = [];
        foreach ($equipments as $equipment) {
            $outputs[] = $this->mapper->map($equipment, EquipmentListItemDataOutput::class);
        }

        return $outputs;
    }
}
