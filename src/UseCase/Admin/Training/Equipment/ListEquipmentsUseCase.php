<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Equipment\EquipmentListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Validator\EmptyDomainValidator;
use App\UseCase\AbstractPublicUseCase;

final class ListEquipmentsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        EmptyDomainValidator $validator,
        private readonly EquipmentProviderGateway $equipmentProvider,
    ) {
        parent::__construct($validator);
    }

    /**
     * @return list<EquipmentListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $equipments = $this->equipmentProvider->findAllForAdminList();

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
