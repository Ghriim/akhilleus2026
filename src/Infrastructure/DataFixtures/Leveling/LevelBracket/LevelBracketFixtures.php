<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\Leveling\LevelBracket;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;
use App\Domain\Gateway\Persister\Leveling\LevelBracket\LevelBracketPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Seeds the v1 baseline leveling curve (three contiguous brackets, last open-ended) from
 * `specifications/v1/initial-requirements.md` §Migration & seeding. The marginal cost to reach
 * level `n` is `coefficientA × n^exponentK + offsetB` for the bracket covering `n`. These rows
 * back the registration baseline (`xpToNextLevel = marginalCostFor(2) = 4000`) and are editable
 * later from the admin (Phase 3.6).
 */
final class LevelBracketFixtures extends Fixture
{
    /** @var list<array{fromLevel: int, toLevel: int|null, coefficientA: int, exponentK: int, offsetB: int}> */
    private const array BRACKETS = [
        ['fromLevel' => 1, 'toLevel' => 10, 'coefficientA' => 1000, 'exponentK' => 2, 'offsetB' => 0],
        ['fromLevel' => 11, 'toLevel' => 20, 'coefficientA' => 3000, 'exponentK' => 2, 'offsetB' => 50000],
        ['fromLevel' => 21, 'toLevel' => null, 'coefficientA' => 500, 'exponentK' => 3, 'offsetB' => 1000000],
    ];

    public function __construct(
        private readonly LevelBracketPersisterGateway $persister,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::BRACKETS as $bracket) {
            $this->persister->create(new LevelBracketDataModel(
                $bracket['fromLevel'],
                $bracket['toLevel'],
                $bracket['coefficientA'],
                $bracket['exponentK'],
                $bracket['offsetB'],
            ));
        }
    }
}
