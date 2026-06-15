<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\CreateQuestDataInput;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;
use Psr\Clock\ClockInterface;

final readonly class CreateQuestValidator extends AbstractLoggedAdminValidator
{
    public const string KIND_METRIC_MISMATCH_CODE = QuestRuleAsserter::KIND_METRIC_MISMATCH_CODE;
    public const string TARGET_VALUE_MISMATCH_CODE = QuestRuleAsserter::TARGET_VALUE_MISMATCH_CODE;
    public const string FAILED_ERROR_CODE = 'CREATE_QUEST_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private ClockInterface $clock,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(CreateQuestDataInput $input): void
    {
        // `dateStart` defaults to now when the admin omits it (the use case applies the same default).
        QuestRuleAsserter::assert(
            $input->kind,
            $input->metric,
            $input->targetValue,
            $input->periodicity,
            $input->rewardedXp,
            $input->dateStart ?? $this->clock->now(),
            $input->dateEnd,
            self::FAILED_ERROR_CODE,
        );
    }
}
