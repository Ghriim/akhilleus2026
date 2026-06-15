<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\UpdateQuestDataInput;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class UpdateQuestValidator extends AbstractLoggedAdminValidator
{
    public const string KIND_METRIC_MISMATCH_CODE = QuestRuleAsserter::KIND_METRIC_MISMATCH_CODE;
    public const string TARGET_VALUE_MISMATCH_CODE = QuestRuleAsserter::TARGET_VALUE_MISMATCH_CODE;
    public const string FAILED_ERROR_CODE = 'UPDATE_QUEST_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(UpdateQuestDataInput $input): void
    {
        QuestRuleAsserter::assert(
            $input->kind,
            $input->metric,
            $input->targetValue,
            $input->periodicity,
            $input->rewardedXp,
            $input->dateStart,
            $input->dateEnd,
            self::FAILED_ERROR_CODE,
        );
    }
}
