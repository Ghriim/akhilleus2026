<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\DeleteEquipmentDataInput;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class DeleteEquipmentValidator extends AbstractLoggedAdminValidator
{
    public function validate(object $input): void
    {
        if (false === $input instanceof DeleteEquipmentDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeleteEquipmentDataInput::class, $input::class));
        }

        // No input-level rule: the use case fetches the entity and throws
        // EntityNotFoundException if the id does not match a row.
    }
}
