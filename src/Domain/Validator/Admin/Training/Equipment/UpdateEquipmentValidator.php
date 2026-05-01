<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\UpdateEquipmentDataInput;
use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class UpdateEquipmentValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'UPDATE_EQUIPMENT_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(UpdateEquipmentDataInput $input, EquipmentDataModel $equipment): void
    {
        $violations = [];
        if ('' === trim($input->label)) {
            $violations['label'][] = 'Label must not be empty.';
        } else {
            $slug = $this->stringDataTransformer->slugify($input->label);
            $existing = $this->equipmentProviderGateway->findOneBySlugForUniqueness($slug);
            if (null !== $existing && $existing->id !== $equipment->id) {
                $violations['label'][] = 'Another equipment already uses this label.';
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Equipment update data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
