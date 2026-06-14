<?php

declare(strict_types=1);

namespace App\Domain\Registry\Questing\Quest;

interface QuestKindRegistry
{
    /** Progress is computed automatically from a tracking/workout metric. */
    public const string AUTOMATIC = 'AUTOMATIC';

    /** No metric; the player claims the reward manually. */
    public const string MANUAL = 'MANUAL';

    /** @var list<string> */
    public const array ALL = [
        self::AUTOMATIC,
        self::MANUAL,
    ];
}
