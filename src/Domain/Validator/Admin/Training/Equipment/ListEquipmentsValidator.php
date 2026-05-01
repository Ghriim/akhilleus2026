<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\ListEquipmentsDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListEquipmentsValidator
{
    public const string ERROR_CODE = 'LIST_EQUIPMENTS_VALIDATION_FAILED';

    /** @var list<string> */
    private const array ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    public function validate(ListEquipmentsDataInput $input): void
    {
        $violations = [];

        if (false === in_array($input->sort, ListEquipmentsDataInput::ALLOWED_SORTS, true)) {
            $violations['sort'][] = sprintf(
                'Sort must be one of: %s.',
                implode(', ', ListEquipmentsDataInput::ALLOWED_SORTS),
            );
        }

        if (false === in_array(strtoupper($input->direction), self::ALLOWED_DIRECTIONS, true)) {
            $violations['direction'][] = sprintf(
                'Direction must be one of: %s.',
                implode(', ', self::ALLOWED_DIRECTIONS),
            );
        }

        if ([] !== $violations) {
            throw new ValidationException('Equipment list query is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
