<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\ListMusclesDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListMusclesValidator
{
    public const string ERROR_CODE = 'LIST_MUSCLES_VALIDATION_FAILED';

    /** @var list<string> */
    private const array ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    public function validate(ListMusclesDataInput $input): void
    {
        $violations = [];

        if (false === in_array($input->sort, ListMusclesDataInput::ALLOWED_SORTS, true)) {
            $violations['sort'][] = sprintf(
                'Sort must be one of: %s.',
                implode(', ', ListMusclesDataInput::ALLOWED_SORTS),
            );
        }

        if (false === in_array(strtoupper($input->direction), self::ALLOWED_DIRECTIONS, true)) {
            $violations['direction'][] = sprintf(
                'Direction must be one of: %s.',
                implode(', ', self::ALLOWED_DIRECTIONS),
            );
        }

        if ([] !== $violations) {
            throw new ValidationException('Muscle list query is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
