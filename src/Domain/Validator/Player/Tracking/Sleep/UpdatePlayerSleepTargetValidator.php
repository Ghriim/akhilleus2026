<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdatePlayerSleepTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdatePlayerSleepTargetValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_PLAYER_SLEEP_TARGET_VALIDATION_FAILED';

    public function validate(UpdatePlayerSleepTargetDataInput $input): void
    {
        $violations = [];
        if (0 >= $input->targetMinutes) {
            $violations['targetMinutes'][] = 'Sleep goal must be a positive number of minutes.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Player sleep target is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
