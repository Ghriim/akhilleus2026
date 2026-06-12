<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer\Workout;

use App\Domain\Registry\Training\Workout\PersonalBestTypeRegistry;

final readonly class PersonalBestValueDataTransformer
{
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
}
