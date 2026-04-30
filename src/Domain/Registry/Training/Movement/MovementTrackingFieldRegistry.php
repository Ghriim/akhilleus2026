<?php

declare(strict_types=1);

namespace App\Domain\Registry\Training\Movement;

interface MovementTrackingFieldRegistry
{
    public const string REPETITIONS = 'REPETITIONS';
    public const string WEIGHT = 'WEIGHT';
    public const string DURATION = 'DURATION';
    public const string DISTANCE = 'DISTANCE';
    public const string INCLINE_PERCENT = 'INCLINE_PERCENT';
    public const string INCLINE_METERS = 'INCLINE_METERS';

    /** @var list<string> */
    public const array ALL = [
        self::REPETITIONS,
        self::WEIGHT,
        self::DURATION,
        self::DISTANCE,
        self::INCLINE_PERCENT,
        self::INCLINE_METERS,
    ];
}
