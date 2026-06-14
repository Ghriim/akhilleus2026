<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Leveling\LevelingConfig;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\UpdateLevelingConfigDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class UpdateLevelingConfigValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'LEVELING_CONFIG_VALIDATION_FAILED';

    public const int MIN_XP_PER_WORKOUT_MINUTE = 50;

    public function validate(UpdateLevelingConfigDataInput $input): void
    {
        $violations = [];

        if (self::MIN_XP_PER_WORKOUT_MINUTE > $input->xpPerWorkoutMinute) {
            $violations['xpPerWorkoutMinute'][] = sprintf(
                'XP per workout minute must be at least %d.',
                self::MIN_XP_PER_WORKOUT_MINUTE,
            );
        }

        if ([] !== $violations) {
            throw new ValidationException('Leveling config update data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
