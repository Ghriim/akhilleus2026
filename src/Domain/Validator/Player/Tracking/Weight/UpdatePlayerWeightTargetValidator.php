<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdatePlayerWeightTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdatePlayerWeightTargetValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_PLAYER_WEIGHT_TARGET_VALIDATION_FAILED';

    public function validate(UpdatePlayerWeightTargetDataInput $input): void
    {
        $violations = [];
        if (0 >= $input->targetGrams) {
            $violations['targetGrams'][] = 'Weight goal must be a positive number of grams.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Player weight target is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
