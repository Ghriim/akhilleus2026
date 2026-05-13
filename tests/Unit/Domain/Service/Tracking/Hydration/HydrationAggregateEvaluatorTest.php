<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Service\Tracking\Hydration\HydrationAggregateEvaluator;
use PHPUnit\Framework\TestCase;

final class HydrationAggregateEvaluatorTest extends TestCase
{
    public function testItSumsEveryEntryValueIntoAmountConsumed(): void
    {
        $summary = self::buildSummary(targetMl: 2000);
        self::attachEntry($summary, valueMl: 250);
        self::attachEntry($summary, valueMl: 500);
        self::attachEntry($summary, valueMl: 100);

        $returned = HydrationAggregateEvaluator::recompute($summary);

        self::assertSame($summary, $returned);
        self::assertSame(850, $summary->amountConsumedMl);
    }

    public function testItResetsToZeroWhenTheSummaryHasNoEntries(): void
    {
        $summary = self::buildSummary(targetMl: 1500);
        $summary->amountConsumedMl = 999; // stale value left from a previous run

        HydrationAggregateEvaluator::recompute($summary);

        self::assertSame(0, $summary->amountConsumedMl);
    }

    public function testItHandlesASingleEntry(): void
    {
        $summary = self::buildSummary(targetMl: 1000);
        self::attachEntry($summary, valueMl: 333);

        HydrationAggregateEvaluator::recompute($summary);

        self::assertSame(333, $summary->amountConsumedMl);
    }

    public function testItRecomputesIdempotentlyOnRepeatedCalls(): void
    {
        $summary = self::buildSummary(targetMl: 2000);
        self::attachEntry($summary, valueMl: 200);
        self::attachEntry($summary, valueMl: 300);

        HydrationAggregateEvaluator::recompute($summary);
        HydrationAggregateEvaluator::recompute($summary);
        HydrationAggregateEvaluator::recompute($summary);

        self::assertSame(500, $summary->amountConsumedMl);
    }

    private static function buildSummary(int $targetMl): HydrationDailySummaryDataModel
    {
        $user = new UserDataModel('player@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';
        $player = new PlayerDataModel($user, 'Tester');

        return new HydrationDailySummaryDataModel($player, new \DateTimeImmutable('2026-05-05'), $targetMl);
    }

    private static function attachEntry(HydrationDailySummaryDataModel $summary, int $valueMl): HydrationEntryDataModel
    {
        $entry = new HydrationEntryDataModel($summary, new \DateTimeImmutable('2026-05-05T10:00:00'), $valueMl);
        $summary->entries->add($entry);

        return $entry;
    }
}
