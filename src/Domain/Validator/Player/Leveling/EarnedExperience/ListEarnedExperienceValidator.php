<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Leveling\EarnedExperience;

use App\Domain\DTO\DataInput\Player\Leveling\EarnedExperience\ListEarnedExperienceDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListEarnedExperienceValidator
{
    public const string ERROR_CODE = 'LIST_EARNED_EXPERIENCE_VALIDATION_FAILED';

    public function validate(ListEarnedExperienceDataInput $input): void
    {
        $violations = [];
        if (1 > $input->page) {
            $violations['page'][] = 'Page must be greater than or equal to 1.';
        }
        if (1 > $input->perPage) {
            $violations['perPage'][] = 'PerPage must be greater than or equal to 1.';
        }
        if (ListEarnedExperienceDataInput::MAX_PER_PAGE < $input->perPage) {
            $violations['perPage'][] = sprintf('PerPage must not exceed %d.', ListEarnedExperienceDataInput::MAX_PER_PAGE);
        }

        if ([] !== $violations) {
            throw new ValidationException('Earned experience journal query is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
