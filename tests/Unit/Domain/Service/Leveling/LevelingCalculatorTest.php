<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service\Leveling;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\Domain\Service\Leveling\LevelingCalculator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class LevelingCalculatorTest extends TestCase
{
    private LevelBracketProviderGateway&MockObject $brackets;
    private LevelingCalculator $calculator;

    protected function setUp(): void
    {
        $this->brackets = $this->createMock(LevelBracketProviderGateway::class);
        $this->calculator = new LevelingCalculator($this->brackets);
        $this->brackets->method('findAllOrderedAsc')->willReturn(self::seededCurve());
    }

    public function testMarginalCostInFirstBracket(): void
    {
        // 1000 × n^2 + 0
        self::assertSame(4000, $this->calculator->marginalCostFor(2));
        self::assertSame(25000, $this->calculator->marginalCostFor(5));
        self::assertSame(100000, $this->calculator->marginalCostFor(10));
    }

    public function testMarginalCostInSecondBracket(): void
    {
        // 3000 × n^2 + 50000
        self::assertSame(3000 * 121 + 50000, $this->calculator->marginalCostFor(11));
        self::assertSame(3000 * 400 + 50000, $this->calculator->marginalCostFor(20));
    }

    public function testMarginalCostInThirdOpenEndedBracket(): void
    {
        // 500 × n^3 + 1000000
        self::assertSame(500 * (21 ** 3) + 1000000, $this->calculator->marginalCostFor(21));
        self::assertSame(500 * (50 ** 3) + 1000000, $this->calculator->marginalCostFor(50));
    }

    public function testApplyEarnedAmountAccumulatesWithoutLevellingUp(): void
    {
        $player = self::buildPlayer();
        $player->level = 1;
        $player->currentXp = 0;
        $player->xpToNextLevel = 4000;

        $this->calculator->applyEarnedAmount($player, 1500);

        self::assertSame(1, $player->level);
        self::assertSame(1500, $player->currentXp);
        self::assertSame(4000, $player->xpToNextLevel);
    }

    public function testApplyEarnedAmountRollsASingleLevelUp(): void
    {
        $player = self::buildPlayer();
        $player->level = 1;
        $player->currentXp = 0;
        $player->xpToNextLevel = 4000;

        $this->calculator->applyEarnedAmount($player, 4000);

        self::assertSame(2, $player->level);
        self::assertSame(0, $player->currentXp);
        self::assertSame(9000, $player->xpToNextLevel); // cost for level 3 = 1000 × 3^2
    }

    public function testApplyEarnedAmountSkipsMultipleLevelsAndKeepsRemainder(): void
    {
        $player = self::buildPlayer();
        $player->level = 1;
        $player->currentXp = 0;
        $player->xpToNextLevel = 4000;

        // 4000 (L1→2) + 9000 (L2→3) + 500 remainder.
        $this->calculator->applyEarnedAmount($player, 13500);

        self::assertSame(3, $player->level);
        self::assertSame(500, $player->currentXp);
        self::assertSame(16000, $player->xpToNextLevel); // cost for level 4 = 1000 × 4^2
    }

    public function testItThrowsWhenNoBracketCoversTheLevel(): void
    {
        $partial = $this->createMock(LevelBracketProviderGateway::class);
        $partial->method('findAllOrderedAsc')->willReturn([self::bracket(1, 10, 1000, 2, 0)]);
        $calculator = new LevelingCalculator($partial);

        $this->expectException(\LogicException::class);

        $calculator->marginalCostFor(11);
    }

    /**
     * @return list<LevelBracketDataModel>
     */
    private static function seededCurve(): array
    {
        return [
            self::bracket(1, 10, 1000, 2, 0),
            self::bracket(11, 20, 3000, 2, 50000),
            self::bracket(21, null, 500, 3, 1000000),
        ];
    }

    private static function bracket(int $from, ?int $to, int $a, int $k, int $b): LevelBracketDataModel
    {
        return new LevelBracketDataModel($from, $to, $a, $k, $b);
    }

    private static function buildPlayer(): PlayerDataModel
    {
        $user = new UserDataModel('lvl@test.test', 'pwd', ['ROLE_PLAYER']);
        $user->password = 'hashed';

        return new PlayerDataModel($user, 'Leveling Hero');
    }
}
