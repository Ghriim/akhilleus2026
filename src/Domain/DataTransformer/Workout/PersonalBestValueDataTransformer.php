<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer\Workout;

use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;
use App\Domain\Registry\Training\Workout\PersonalBestTypeRegistry;

final readonly class PersonalBestValueDataTransformer
{
    /**
     * ObjectMapper transform callable for `PersonalBestEntryDataOutput::$value`: combines the
     * source's `type` with the formatted raw `value` into the human-displayable string. Mapped
     * from the `value` source property, so the first argument is the raw numeric string and the
     * second is the full `PersonalBestDataModel` source the mapper passes alongside it.
     *
     * @param numeric-string $value
     */
    public static function displayableForOutput(string $value, PersonalBestDataModel $personalBest): string
    {
        return self::displayableValue($personalBest->type, self::formatValue($value));
    }

    public static function displayableValue(string $personalBestType, string $value): string
    {
        if (true === in_array(
            $personalBestType,
            [
                PersonalBestTypeRegistry::HIGHEST_WEIGHT,
                PersonalBestTypeRegistry::HIGHEST_VOLUME_ONE_SET,
                PersonalBestTypeRegistry::HIGHEST_VOLUME_WORKOUT,
            ]
        )) {
            return $value.' kg';
        }

        if (PersonalBestTypeRegistry::HIGHEST_DISTANCE === $personalBestType) {
            return $value.' km';
        }

        if (PersonalBestTypeRegistry::HIGHEST_SPEED === $personalBestType) {
            return $value.' km/h';
        }

        if (PersonalBestTypeRegistry::HIGHEST_DURATION === $personalBestType) {
            return $value.' h';
        }

        if (PersonalBestTypeRegistry::HIGHEST_REPS === $personalBestType) {
            return $value.' reps';
        }

        return $value;
    }

    /**
     * Rounds to 2 decimals, uses comma separator, drops the fractional part
     * entirely when it is zero (e.g. "100.0000" → "100", "100.5" → "100,50").
     *
     * @param numeric-string $numeric
     */
    private static function formatValue(string $numeric): string
    {
        $formatted = number_format((float) $numeric, 2, ',', '');

        return str_ends_with($formatted, ',00') ? substr($formatted, 0, -3) : $formatted;
    }
}
