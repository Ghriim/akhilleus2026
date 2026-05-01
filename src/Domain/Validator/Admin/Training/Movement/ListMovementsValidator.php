<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\ListMovementsDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListMovementsValidator
{
    public const string ERROR_CODE = 'LIST_MOVEMENTS_VALIDATION_FAILED';

    /** @var list<string> */
    private const array ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    public function validate(ListMovementsDataInput $input): void
    {
        $violations = [];

        if (false === in_array($input->sort, ListMovementsDataInput::ALLOWED_SORTS, true)) {
            $violations['sort'][] = sprintf(
                'Sort must be one of: %s.',
                implode(', ', ListMovementsDataInput::ALLOWED_SORTS),
            );
        }

        if (false === in_array(strtoupper($input->direction), self::ALLOWED_DIRECTIONS, true)) {
            $violations['direction'][] = sprintf(
                'Direction must be one of: %s.',
                implode(', ', self::ALLOWED_DIRECTIONS),
            );
        }

        if ([] !== $violations) {
            throw new ValidationException('Movement list query is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
