<?php

declare(strict_types=1);

namespace App\Domain\Registry\Questing\Quest;

interface QuestPeriodicityRegistry
{
    /** One progression per (player, quest) for the whole lifetime of the quest. */
    public const string UNIQUE = 'UNIQUE';

    /** A fresh progression each calendar day (Europe/Paris). */
    public const string DAILY = 'DAILY';

    /** A fresh progression each ISO week (Monday–Sunday, Europe/Paris). */
    public const string WEEKLY = 'WEEKLY';

    /** A fresh progression each calendar month (Europe/Paris). */
    public const string MONTHLY = 'MONTHLY';

    /** @var list<string> */
    public const array ALL = [
        self::UNIQUE,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
    ];

    /** Periodicities that recur on a period boundary (everything but UNIQUE). */
    public const array RECURRING = [
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
    ];
}
