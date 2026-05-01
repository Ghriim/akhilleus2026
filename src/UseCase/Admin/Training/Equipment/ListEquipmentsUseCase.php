<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\ListEquipmentsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Validator\Admin\Training\Equipment\ListEquipmentsValidator;
use App\UseCase\AbstractPublicUseCase;

final class ListEquipmentsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly ListEquipmentsValidator $listEquipmentsValidator,
        private readonly EquipmentProviderGateway $equipmentProvider,
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

        return array_map(
            static fn ($equipment) => new EquipmentListItemDataOutput(
                $equipment->id,
                $equipment->slug,
                $equipment->label,
            ),
            $equipments,
        );
    }
}
