<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Questing;

use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;
use App\Domain\Service\Questing\QuestPeriodResolver;
use PHPUnit\Framework\TestCase;

final class QuestPeriodResolverTest extends TestCase
{
    private QuestPeriodResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new QuestPeriodResolver();
    }

    // 2026-06-10 14:30 UTC = 16:30 Europe/Paris (CEST, +2), a Wednesday. Week: Mon 8th → Sun 14th.
    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-06-10 14:30:00', new \DateTimeZone('UTC'));
    }

    public function testDailyWindowSpansTheParisCalendarDay(): void
    {
        $period = $this->resolver->resolve(QuestPeriodicityRegistry::DAILY, $this->now());

        self::assertNotNull($period['startDate']);
        self::assertNotNull($period['endDate']);
        self::assertSame('2026-06-10 00:00:00', $period['startDate']->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-10 23:59:59', $period['endDate']->format('Y-m-d H:i:s'));
        self::assertSame('Europe/Paris', $period['startDate']->getTimezone()->getName());
    }

    public function testWeeklyWindowSpansMondayToSunday(): void
    {
        $period = $this->resolver->resolve(QuestPeriodicityRegistry::WEEKLY, $this->now());

        self::assertNotNull($period['startDate']);
        self::assertNotNull($period['endDate']);
        self::assertSame('2026-06-08 00:00:00', $period['startDate']->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-14 23:59:59', $period['endDate']->format('Y-m-d H:i:s'));
    }

    public function testMonthlyWindowSpansTheWholeMonth(): void
    {
        $period = $this->resolver->resolve(QuestPeriodicityRegistry::MONTHLY, $this->now());

        self::assertNotNull($period['startDate']);
        self::assertNotNull($period['endDate']);
        self::assertSame('2026-06-01 00:00:00', $period['startDate']->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-30 23:59:59', $period['endDate']->format('Y-m-d H:i:s'));
    }

    public function testUniqueHasNoWindow(): void
    {
        $period = $this->resolver->resolve(QuestPeriodicityRegistry::UNIQUE, $this->now());

        self::assertNull($period['startDate']);
        self::assertNull($period['endDate']);
    }

    public function testItRejectsAnUnknownPeriodicity(): void
    {
        $this->expectException(\LogicException::class);

        $this->resolver->resolve('FORTNIGHTLY', $this->now());
    }
}
