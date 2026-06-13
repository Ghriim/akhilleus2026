<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Leveling\LevelBracket;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One contiguous slice of the leveling curve: for any level `n` in `[fromLevel, toLevel]`
 * (`toLevel = null` means open-ended, last bracket), the marginal XP cost of the next level is
 * `coefficientA × n^exponentK + offsetB`. The full curve is reloaded by `LevelingCalculator`
 * (Phase 3.2); the contiguity / single-open-ended invariants are enforced by the admin
 * validators (Phase 3.6).
 */
#[ORM\Entity]
#[ORM\Table(name: 'level_bracket')]
#[ORM\UniqueConstraint(name: 'uniq_level_bracket_from_level', columns: ['from_level'])]
class LevelBracketDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\Column(type: Types::INTEGER)]
    public int $fromLevel;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $toLevel;

    #[ORM\Column(type: Types::INTEGER)]
    public int $coefficientA;

    #[ORM\Column(type: Types::INTEGER)]
    public int $exponentK;

    #[ORM\Column(type: Types::INTEGER)]
    public int $offsetB;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        int $fromLevel,
        ?int $toLevel,
        int $coefficientA,
        int $exponentK,
        int $offsetB,
    ) {
        $this->fromLevel = $fromLevel;
        $this->toLevel = $toLevel;
        $this->coefficientA = $coefficientA;
        $this->exponentK = $exponentK;
        $this->offsetB = $offsetB;
    }
}
