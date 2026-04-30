<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\UpdateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedUserValidator;

final readonly class UpdateEquipmentValidator extends AbstractLoggedUserValidator
{
    public const string ERROR_CODE = 'UPDATE_EQUIPMENT_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(object $input): void
    {
        if (false === $input instanceof UpdateEquipmentDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', UpdateEquipmentDataInput::class, $input::class));
        }

        $violations = [];
        if ('' === trim($input->label)) {
            $violations['label'][] = 'Label must not be empty.';
        } else {
            $slug = $this->stringDataTransformer->slugify($input->label);
            $existing = $this->equipmentProviderGateway->findOneBySlugForUniqueness($slug);
            if (null !== $existing && $existing->id !== $input->id) {
                $violations['label'][] = 'Another equipment already uses this label.';
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Equipment update data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
