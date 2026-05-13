<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Tracking\Hydration;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'hydration_daily_summary')]
#[ORM\UniqueConstraint(name: 'uniq_hydration_daily_summary_player_date', columns: ['player_id', 'date'])]
class HydrationDailySummaryDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: PlayerDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public PlayerDataModel $player;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public \DateTimeImmutable $date;

    /**
     * Snapshotted from the player's `dailyHydrationTargetMl` at create time, then editable
     * per-day. Editing the player's global default afterwards does not change this value.
     */
    #[ORM\Column(type: Types::INTEGER)]
    public int $targetMl;

    /**
     * Auto-derived: sum of this summary's `HydrationEntry.valueMl`. Recomputed by
     * `HydrationAggregateEvaluator` from the persister on every entry create / update / delete.
     * Defaults to 0 so a freshly created Summary with no entries reads correctly.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $amountConsumedMl = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    /** @var Collection<int, HydrationEntryDataModel> */
    #[ORM\OneToMany(targetEntity: HydrationEntryDataModel::class, mappedBy: 'summary', orphanRemoval: true)]
    public Collection $entries;

    public function __construct(
        PlayerDataModel $player,
        \DateTimeImmutable $date,
        int $targetMl,
    ) {
        $this->player = $player;
        $this->date = $date;
        $this->targetMl = $targetMl;
        $this->entries = new ArrayCollection();
    }
}
