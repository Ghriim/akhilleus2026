<?php

declare(strict_types=1);

namespace App\Domain\Registry\Workout;

interface WorkoutStatusRegistry
{
    public const string PLANNED = 'PLANNED';
    public const string IN_PROGRESS = 'IN_PROGRESS';
    public const string COMPLETED = 'COMPLETED';
    public const string CANCELED = 'CANCELED';

    /** @var list<string> */
    public const array ALL = [
        self::PLANNED,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::CANCELED,
    ];
}
