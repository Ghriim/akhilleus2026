<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Equipment\CreateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedUserValidator;

final readonly class CreateEquipmentValidator extends AbstractLoggedUserValidator
{
    public const string ERROR_CODE = 'CREATE_EQUIPMENT_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(object $input): void
    {
        if (false === $input instanceof CreateEquipmentDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', CreateEquipmentDataInput::class, $input::class));
        }

        $violations = [];
        if ('' === trim($input->label)) {
            $violations['label'][] = 'Label must not be empty.';
        } else {
            $slug = $this->stringDataTransformer->slugify($input->label);
            if (null !== $this->equipmentProviderGateway->findOneBySlugForUniqueness($slug)) {
                $violations['label'][] = 'Another equipment already uses this label.';
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Equipment creation data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
