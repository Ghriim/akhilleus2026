<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\UpdateQuestDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\Admin\Questing\Quest\UpdateQuestValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class UpdateQuestValidatorTest extends TestCase
{
    private LoggedUserResolverInterface&MockObject $loggedUserResolver;
    private UpdateQuestValidator $validator;

    protected function setUp(): void
    {
        $this->loggedUserResolver = $this->createMock(LoggedUserResolverInterface::class);
        $this->validator = new UpdateQuestValidator($this->loggedUserResolver);
    }

    public function testItPassesForAValidAutomaticQuest(): void
    {
        $this->validator->validate(new UpdateQuestDataInput(
            '01HZX000000000000000000001',
            'Hydrate 1.5L',
            QuestKindRegistry::AUTOMATIC,
            QuestPeriodicityRegistry::DAILY,
            100,
            QuestMetricRegistry::HYDRATION_ML_DAILY,
            '1500',
            new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
            null,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAManualQuestCarryingATargetValue(): void
    {
        try {
            $this->validator->validate(new UpdateQuestDataInput(
                '01HZX000000000000000000001',
                'Manual with target',
                QuestKindRegistry::MANUAL,
                QuestPeriodicityRegistry::DAILY,
                100,
                null,
                '1500',
                new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
                null,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateQuestValidator::TARGET_VALUE_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('targetValue', $e->violations);
        }
    }

    public function testItRejectsAnUnknownMetric(): void
    {
        try {
            $this->validator->validate(new UpdateQuestDataInput(
                '01HZX000000000000000000001',
                'Bad metric',
                QuestKindRegistry::AUTOMATIC,
                QuestPeriodicityRegistry::DAILY,
                100,
                'CALORIES_BURNED',
                '500',
                new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
                null,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateQuestValidator::KIND_METRIC_MISMATCH_CODE, $e->errorCode);
            self::assertArrayHasKey('metric', $e->violations);
        }
    }

    public function testItRejectsAnEndDateNotAfterTheStartDate(): void
    {
        try {
            $this->validator->validate(new UpdateQuestDataInput(
                '01HZX000000000000000000001',
                'Bad window',
                QuestKindRegistry::MANUAL,
                QuestPeriodicityRegistry::DAILY,
                100,
                null,
                null,
                new \DateTimeImmutable('2026-06-10T00:00:00+00:00'),
                new \DateTimeImmutable('2026-06-05T00:00:00+00:00'),
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateQuestValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('dateEnd', $e->violations);
        }
    }

    public function testItRejectsAnUnknownKind(): void
    {
        try {
            $this->validator->validate(new UpdateQuestDataInput(
                '01HZX000000000000000000001',
                'Bad kind',
                'EPIC',
                QuestPeriodicityRegistry::DAILY,
                100,
                null,
                null,
                new \DateTimeImmutable('2026-06-01T00:00:00+00:00'),
                null,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateQuestValidator::FAILED_ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('kind', $e->violations);
        }
    }
}
