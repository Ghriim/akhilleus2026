<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Leveling;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\Service\Leveling\LevelCurveEvaluator;
use PHPUnit\Framework\TestCase;

final class LevelCurveEvaluatorTest extends TestCase
{
    public function testFieldViolationsAreEmptyForAValidBracket(): void
    {
        self::assertSame([], LevelCurveEvaluator::collectFieldViolations(1, 10, 2));
        self::assertSame([], LevelCurveEvaluator::collectFieldViolations(21, null, 3));
    }

    public function testFieldViolationsFlagFromLevelBelowOne(): void
    {
        $violations = LevelCurveEvaluator::collectFieldViolations(0, 10, 2);

        self::assertArrayHasKey('fromLevel', $violations);
    }

    public function testFieldViolationsFlagToLevelBelowFromLevel(): void
    {
        $violations = LevelCurveEvaluator::collectFieldViolations(10, 5, 2);

        self::assertArrayHasKey('toLevel', $violations);
    }

    public function testFieldViolationsFlagExponentBelowOne(): void
    {
        $violations = LevelCurveEvaluator::collectFieldViolations(1, 10, 0);

        self::assertArrayHasKey('exponentK', $violations);
    }

    public function testCurveViolationsAreEmptyForTheSeedBaselineCurve(): void
    {
        $curve = [
            new LevelBracketDataModel(1, 10, 1000, 2, 0),
            new LevelBracketDataModel(11, 20, 3000, 2, 50000),
            new LevelBracketDataModel(21, null, 500, 3, 1000000),
        ];

        self::assertSame([], LevelCurveEvaluator::collectCurveViolations($curve));
    }

    public function testCurveViolationsAreEmptyForASingleOpenEndedBracket(): void
    {
        self::assertSame([], LevelCurveEvaluator::collectCurveViolations([new LevelBracketDataModel(1, null, 1000, 2, 0)]));
    }

    public function testCurveMustStartAtLevelOne(): void
    {
        $violations = LevelCurveEvaluator::collectCurveViolations([new LevelBracketDataModel(2, null, 1000, 2, 0)]);

        self::assertContains('The first bracket must start at level 1.', $violations);
    }

    public function testExactlyOneOpenEndedBracketIsRequired(): void
    {
        $curve = [
            new LevelBracketDataModel(1, 10, 1000, 2, 0),
            new LevelBracketDataModel(11, 20, 3000, 2, 50000),
        ];

        $violations = LevelCurveEvaluator::collectCurveViolations($curve);

        self::assertContains('Exactly one bracket must be open-ended (the last one, with toLevel = null).', $violations);
    }

    public function testOnlyTheLastBracketMayBeOpenEnded(): void
    {
        $curve = [
            new LevelBracketDataModel(1, null, 1000, 2, 0),
            new LevelBracketDataModel(11, 20, 3000, 2, 50000),
        ];

        $violations = LevelCurveEvaluator::collectCurveViolations($curve);

        self::assertContains('Only the last bracket may be open-ended (toLevel = null).', $violations);
    }

    public function testOverlappingBracketsAreRejected(): void
    {
        $curve = [
            new LevelBracketDataModel(1, 10, 1000, 2, 0),
            new LevelBracketDataModel(8, null, 3000, 2, 0),
        ];

        $violations = LevelCurveEvaluator::collectCurveViolations($curve);

        self::assertNotEmpty(array_filter($violations, static fn (string $m): bool => str_contains($m, 'overlap')));
    }

    public function testNonContiguousBracketsAreRejected(): void
    {
        $curve = [
            new LevelBracketDataModel(1, 10, 1000, 2, 0),
            new LevelBracketDataModel(15, null, 3000, 2, 0),
        ];

        $violations = LevelCurveEvaluator::collectCurveViolations($curve);

        self::assertNotEmpty(array_filter($violations, static fn (string $m): bool => str_contains($m, 'contiguous')));
    }

    public function testNonPositiveMarginalCostIsRejected(): void
    {
        // a×n^k + b at level 1 = -1000×1 + 0 = -1000 < 0.
        $violations = LevelCurveEvaluator::collectCurveViolations([new LevelBracketDataModel(1, null, -1000, 2, 0)]);

        self::assertNotEmpty(array_filter($violations, static fn (string $m): bool => str_contains($m, 'marginal cost')));
    }

    public function testSortByFromLevelOrdersAscending(): void
    {
        $sorted = LevelCurveEvaluator::sortByFromLevel([
            new LevelBracketDataModel(21, null, 500, 3, 1000000),
            new LevelBracketDataModel(1, 10, 1000, 2, 0),
            new LevelBracketDataModel(11, 20, 3000, 2, 50000),
        ]);

        self::assertSame([1, 11, 21], array_map(static fn (LevelBracketDataModel $b): int => $b->fromLevel, $sorted));
    }
}
