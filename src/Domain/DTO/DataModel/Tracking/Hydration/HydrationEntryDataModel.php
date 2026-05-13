<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Tracking\Hydration;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'hydration_entry')]
class HydrationEntryDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: HydrationDailySummaryDataModel::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    public HydrationDailySummaryDataModel $summary;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $loggedAt;

    #[ORM\Column(type: Types::INTEGER)]
    public int $valueMl;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    /**
     * Virtual property — Doctrine ignores it (no `#[ORM\Column]`). Implements
     * `OwnedByPlayerInterface` by reaching the player transitively through `summary->player`,
     * mirroring the v0 pattern on `ExerciseDataModel` / `ExerciseSetDataModel`.
     */
    public PlayerDataModel $player {
        get => $this->summary->player;
    }

    public function __construct(
        HydrationDailySummaryDataModel $summary,
        \DateTimeImmutable $loggedAt,
        int $valueMl,
    ) {
        $this->summary = $summary;
        $this->loggedAt = $loggedAt;
        $this->valueMl = $valueMl;
    }
}
