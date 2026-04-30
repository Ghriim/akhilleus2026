<?php

declare(strict_types=1);

namespace App\Domain\Registry\Training\Workout;

interface PersonalBestTypeRegistry
{
    public const string HIGHEST_WEIGHT = 'HIGHEST_WEIGHT';
    public const string HIGHEST_REPS = 'HIGHEST_REPS';
    public const string HIGHEST_VOLUME_ONE_SET = 'HIGHEST_VOLUME_ONE_SET';
    public const string HIGHEST_VOLUME_WORKOUT = 'HIGHEST_VOLUME_WORKOUT';
    public const string HIGHEST_DURATION = 'HIGHEST_DURATION';
    public const string HIGHEST_DISTANCE = 'HIGHEST_DISTANCE';
    public const string HIGHEST_SPEED = 'HIGHEST_SPEED';

    /** @var list<string> */
    public const array ALL = [
        self::HIGHEST_WEIGHT,
        self::HIGHEST_REPS,
        self::HIGHEST_VOLUME_ONE_SET,
        self::HIGHEST_VOLUME_WORKOUT,
        self::HIGHEST_DURATION,
        self::HIGHEST_DISTANCE,
        self::HIGHEST_SPEED,
    ];
}
