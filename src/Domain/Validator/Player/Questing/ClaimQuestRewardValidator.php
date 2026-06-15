<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Questing;

use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;

/**
 * Standalone (ownership is enforced upstream by the player-scoped progression lookup). Validates
 * that the progression is claimable and that the quest's reward window is still open.
 */
final readonly class ClaimQuestRewardValidator
{
    public const string ERROR_CODE = 'CLAIM_QUEST_REWARD_VALIDATION_FAILED';

    public function validate(QuestProgressionDataModel $progression, \DateTimeImmutable $now): void
    {
        $violations = [];

        if (QuestProgressionStatusRegistry::CLAIMABLE !== $progression->status) {
            $violations['status'][] = 'Only a claimable quest can be rewarded.';
        }

        $dateEnd = $progression->quest->dateEnd;
        if (null !== $dateEnd && $dateEnd < $now) {
            $violations['window'][] = 'The quest reward window has closed.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Quest reward claim is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
