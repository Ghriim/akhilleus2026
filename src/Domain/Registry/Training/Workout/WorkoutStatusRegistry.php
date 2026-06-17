<?php

declare(strict_types=1);

namespace App\Domain\Registry\Training\Workout;

interface WorkoutStatusRegistry
{
    public const string PLANNED = 'PLANNED';
    public const string IN_PROGRESS = 'IN_PROGRESS';
    public const string COMPLETED = 'COMPLETED';
    public const string CANCELED = 'CANCELED';
    public const string DELETED = 'DELETED';

    /** @var list<string> */
    public const array ALL = [
        self::PLANNED,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::CANCELED,
        self::DELETED,
    ];

    /** @var list<string> */
    public const array EDITABLE_STATUSES = [
        self::PLANNED,
        self::IN_PROGRESS,
    ];

    /** @var list<string> */
    public const array CANCELLABLE_STATUSES = [
        self::PLANNED,
        self::IN_PROGRESS,
    ];
}
