<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing;

use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;

/**
 * Turns a quest periodicity + the current instant into the `[startDate, endDate]` window a
 * `QuestProgression` covers. Day boundaries are computed in the app timezone (Europe/Paris), per
 * the v1 "Cron timezone" decision. `UNIQUE` quests have no window (both bounds null).
 */
final readonly class QuestPeriodResolver
{
    private const string APP_TIMEZONE = 'Europe/Paris';

    /**
     * @return array{startDate: ?\DateTimeImmutable, endDate: ?\DateTimeImmutable}
     */
    public function resolve(string $periodicity, \DateTimeImmutable $now): array
    {
        $local = $now->setTimezone(new \DateTimeZone(self::APP_TIMEZONE));

        return match ($periodicity) {
            QuestPeriodicityRegistry::DAILY => [
                'startDate' => $local->setTime(0, 0, 0),
                'endDate' => $local->setTime(23, 59, 59),
            ],
            QuestPeriodicityRegistry::WEEKLY => [
                'startDate' => $local->modify('monday this week')->setTime(0, 0, 0),
                'endDate' => $local->modify('sunday this week')->setTime(23, 59, 59),
            ],
            QuestPeriodicityRegistry::MONTHLY => [
                'startDate' => $local->modify('first day of this month')->setTime(0, 0, 0),
                'endDate' => $local->modify('last day of this month')->setTime(23, 59, 59),
            ],
            QuestPeriodicityRegistry::UNIQUE => [
                'startDate' => null,
                'endDate' => null,
            ],
            default => throw new \LogicException(sprintf('Unknown quest periodicity "%s".', $periodicity)),
        };
    }
}
