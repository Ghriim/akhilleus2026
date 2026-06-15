<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validator\Player\Questing;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;
use App\Domain\Validator\Player\Questing\ClaimQuestRewardValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ClaimQuestRewardValidatorTest extends TestCase
{
    private ClaimQuestRewardValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ClaimQuestRewardValidator();
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-06-10 12:00:00');
    }

    private function progression(string $status, ?\DateTimeImmutable $questDateEnd): QuestProgressionDataModel
    {
        $quest = new QuestDataModel(
            'Drink water',
            QuestKindRegistry::MANUAL,
            QuestPeriodicityRegistry::DAILY,
            new \DateTimeImmutable('2026-06-01 00:00:00'),
            100,
            dateEnd: $questDateEnd,
        );

        return new QuestProgressionDataModel($quest, $this->createMock(PlayerDataModel::class), $status);
    }

    public function testItPassesForAClaimableProgressionWithinTheWindow(): void
    {
        $this->validator->validate(
            $this->progression(QuestProgressionStatusRegistry::CLAIMABLE, new \DateTimeImmutable('2026-12-31 23:59:59')),
            $this->now(),
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItPassesWhenTheQuestHasNoEndDate(): void
    {
        $this->validator->validate(
            $this->progression(QuestProgressionStatusRegistry::CLAIMABLE, null),
            $this->now(),
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsANonClaimableProgression(): void
    {
        try {
            $this->validator->validate(
                $this->progression(QuestProgressionStatusRegistry::IN_PROGRESS, null),
                $this->now(),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ClaimQuestRewardValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('status', $e->violations);
        }
    }

    public function testItRejectsAClosedRewardWindow(): void
    {
        try {
            $this->validator->validate(
                $this->progression(QuestProgressionStatusRegistry::CLAIMABLE, new \DateTimeImmutable('2026-06-09 23:59:59')),
                $this->now(),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(ClaimQuestRewardValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('window', $e->violations);
        }
    }
}
