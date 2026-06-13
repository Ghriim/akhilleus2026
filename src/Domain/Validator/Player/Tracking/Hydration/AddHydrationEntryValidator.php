<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\AddHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class AddHydrationEntryValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'ADD_HYDRATION_ENTRY_VALIDATION_FAILED';

    public function validate(AddHydrationEntryDataInput $input): void
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
