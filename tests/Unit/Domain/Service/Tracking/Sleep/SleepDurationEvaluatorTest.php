<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Service\Tracking\Sleep\SleepDurationEvaluator;
use PHPUnit\Framework\TestCase;

final class SleepDurationEvaluatorTest extends TestCase
{
    public function testItComputesAnExactEightHourSleep(): void
    {
        $entry = self::buildEntry(
            bedAt: '2026-05-05T23:00:00',
            wakeAt: '2026-05-06T07:00:00',
            wakeDate: '2026-05-06',
        );

        $returned = SleepDurationEvaluator::recompute($entry);

        self::assertSame($entry, $returned);
        self::assertSame(480, $entry->durationMinutes);
    }

    public function testItComputesASleepThatDoesNotCrossMidnight(): void
    {
        $entry = self::buildEntry(
            bedAt: '2026-05-05T01:30:00',
            wakeAt: '2026-05-05T08:00:00',
            wakeDate: '2026-05-05',
        );

        SleepDurationEvaluator::recompute($entry);

        self::assertSame(390, $entry->durationMinutes); // 6h30 → 390 min
    }

    public function testItFloorsTheRemainderOnPartialMinutes(): void
    {
        $entry = self::buildEntry(
            bedAt: '2026-05-05T23:00:00',
            wakeAt: '2026-05-06T06:59:45', // 7h59m45s = 28785s = 479.75 min
            wakeDate: '2026-05-06',
        );

        SleepDurationEvaluator::recompute($entry);

        self::assertSame(479, $entry->durationMinutes);
    }

    public function testItOverwritesAStaleDurationOnRepeatedCalls(): void
    {
        $entry = self::buildEntry(
            bedAt: '2026-05-05T22:00:00',
            wakeAt: '2026-05-06T06:00:00',
            wakeDate: '2026-05-06',
        );
        $entry->durationMinutes = 9999; // stale

        SleepDurationEvaluator::recompute($entry);

        self::assertSame(480, $entry->durationMinutes);
    }

    private static function buildEntry(string $bedAt, string $wakeAt, string $wakeDate): SleepDailyEntryDataModel
    {
        $user = new UserDataModel('player@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';
        $player = new PlayerDataModel($user, 'Tester');

        return new SleepDailyEntryDataModel(
            $player,
            new \DateTimeImmutable($wakeDate),
            new \DateTimeImmutable($bedAt),
            new \DateTimeImmutable($wakeAt),
        );
    }
}
