<?php

declare(strict_types=1);

namespace App\Domain\Registry\Questing\QuestProgression;

interface QuestProgressionStatusRegistry
{
    /** Target not yet reached (automatic quests only — manual quests start CLAIMABLE). */
    public const string IN_PROGRESS = 'IN_PROGRESS';

    /** The reward can be claimed. */
    public const string CLAIMABLE = 'CLAIMABLE';

    /** The reward has been claimed (an EarnedExperience was created). */
    public const string REWARDED = 'REWARDED';

    /** @var list<string> */
    public const array ALL = [
        self::IN_PROGRESS,
        self::CLAIMABLE,
        self::REWARDED,
    ];
}
