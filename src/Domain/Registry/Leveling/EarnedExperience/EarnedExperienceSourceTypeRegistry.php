<?php

declare(strict_types=1);

namespace App\Domain\Registry\Leveling\EarnedExperience;

interface EarnedExperienceSourceTypeRegistry
{
    public const string QUEST = 'quest';
    public const string WORKOUT = 'workout';

    /** @var list<string> */
    public const array ALL = [
        self::QUEST,
        self::WORKOUT,
    ];
}
