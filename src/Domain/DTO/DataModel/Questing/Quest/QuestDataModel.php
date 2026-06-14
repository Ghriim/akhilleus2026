<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Questing\Quest;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An admin-defined challenge. `kind = AUTOMATIC` quests track a `metric` toward a `targetValue`
 * (both non-null); `kind = MANUAL` quests carry no metric/target and are claimable by default.
 * `periodicity` decides how often a fresh `QuestProgression` is materialised per player. The quest
 * is active for any instant in `[dateStart, dateEnd]` (`dateEnd = null` = open-ended).
 * `targetValue` follows the v0 numeric-string convention (`DECIMAL` mapped to `?string`).
 */
#[ORM\Entity]
#[ORM\Table(name: 'quest')]
class QuestDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $label;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $kind;

    #[ORM\Column(type: Types::STRING, length: 40, nullable: true)]
    public ?string $metric = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $periodicity;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    public ?string $targetValue = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $dateStart;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $dateEnd = null;

    #[ORM\Column(type: Types::INTEGER)]
    public int $rewardedXp;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        string $label,
        string $kind,
        string $periodicity,
        \DateTimeImmutable $dateStart,
        int $rewardedXp,
        ?string $metric = null,
        ?string $targetValue = null,
        ?\DateTimeImmutable $dateEnd = null,
    ) {
        $this->label = $label;
        $this->kind = $kind;
        $this->periodicity = $periodicity;
        $this->dateStart = $dateStart;
        $this->rewardedXp = $rewardedXp;
        $this->metric = $metric;
        $this->targetValue = $targetValue;
        $this->dateEnd = $dateEnd;
    }
}
