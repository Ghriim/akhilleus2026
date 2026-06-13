<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateHydrationEntryValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_HYDRATION_ENTRY_VALIDATION_FAILED';

    public function validate(UpdateHydrationEntryDataInput $input): void
    {
        $violations = [];
        if (0 >= $input->valueMl) {
            $violations['valueMl'][] = 'Hydration entry must be a positive number of milliliters.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Hydration entry is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
