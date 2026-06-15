<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\CreateQuestDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Questing\Quest\CreateQuestValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[AllowMockObjectsWithoutExpectations]
final class CreateQuestValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private ClockInterface&MockObject $clock;
    private CreateQuestValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-06-15T10:00:00+00:00'));
        $this->validator = new CreateQuestValidator($this->loggedUserResolver, $this->clock);
    }

    public function testItPassesForAValidAutomaticQuest(): void
    {
        $this->validator->validate(new CreateQuestDataInput(
            'Hydrate 1.5L',
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            100,
            QuestMetricRegistry::HYDRATION_ML_DAILY,
            '1500',
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesForAValidManualQuest(): void
    {
        $this->validator->validate(new CreateQuestDataInput(
            'Stretch session',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::WEEKLY,
            50,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnAutomaticQuestWithoutAMetric(): void
    {
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'No metric',
                QuestKindRegistry::AUTOMATIC,
                QuestPeriodicityRegistry::DAILY,
                100,
                null,
                '1500',
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::KIND_METRIC_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('metric', $e->violations);
        }
    }

    public function testItRejectsAManualQuestCarryingAMetric(): void
    {
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'Manual with metric',
                QuestKindRegistry::MANUAL,
                QuestPeriodicityRegistry::DAILY,
                100,
                QuestMetricRegistry::STEPS_DAILY,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::KIND_METRIC_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('metric', $e->violations);
        }
    }

    public function testItRejectsAnAutomaticQuestWithoutATargetValue(): void
    {
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'No target',
                QuestKindRegistry::AUTOMATIC,
                QuestPeriodicityRegistry::DAILY,
                100,
                QuestMetricRegistry::STEPS_DAILY,
                null,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::TARGET_VALUE_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('targetValue', $e->violations);
        }
    }

    public function testItRejectsANonPositiveTargetValue(): void
    {
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'Zero target',
                QuestKindRegistry::AUTOMATIC,
                QuestPeriodicityRegistry::DAILY,
                100,
                QuestMetricRegistry::STEPS_DAILY,
                '0',
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::TARGET_VALUE_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('targetValue', $e->violations);
        }
    }

    public function testItRejectsAnUnknownPeriodicity(): void
    {
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'Bad periodicity',
                QuestKindRegistry::MANUAL,
                'FORTNIGHTLY',
                100,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('periodicity', $e->violations);
        }
    }

    public function testItRejectsANonPositiveRewardedXp(): void
    {
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'No reward',
                QuestKindRegistry::MANUAL,
                QuestPeriodicityRegistry::DAILY,
                0,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('rewardedXp', $e->violations);
        }
    }

    public function testItRejectsAnEndDateNotAfterTheStartDate(): void
    {
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'Bad window',
                QuestKindRegistry::MANUAL,
                QuestPeriodicityRegistry::DAILY,
                100,
                null,
                null,
                new \DateTimeImmutable('2026-06-10T00:00:00+00:00'),
                new \DateTimeImmutable('2026-06-10T00:00:00+00:00'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('dateEnd', $e->violations);
        }
    }

    public function testItUsesTheClockToDefaultStartDateWhenComparingTheEndDate(): void
    {
        // dateStart omitted → defaults to clock now (2026-06-15); an end date before that is invalid.
        try {
            $this->validator->validate(new CreateQuestDataInput(
                'Default start',
                QuestKindRegistry::MANUAL,
                QuestPeriodicityRegistry::DAILY,
                100,
                null,
                null,
                null,
                new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateQuestValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('dateEnd', $e->violations);
        }
    }
}
