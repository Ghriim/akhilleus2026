<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Equipment;

use App\Domain\DTO\DataInput\Admin\Training\Equipment\ListEquipmentsDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\DomainValidatorInterface;

final readonly class ListEquipmentsValidator implements DomainValidatorInterface
{
    public const string ERROR_CODE = 'LIST_EQUIPMENTS_VALIDATION_FAILED';

    /** @var list<string> */
    private const array ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    public function validate(object $input): void
    {
        if (false === $input instanceof ListEquipmentsDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', ListEquipmentsDataInput::class, $input::class));
        }

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
