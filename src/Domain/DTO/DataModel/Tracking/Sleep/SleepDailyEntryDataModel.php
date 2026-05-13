<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Tracking\Sleep;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sleep_daily_entry')]
#[ORM\UniqueConstraint(name: 'uniq_sleep_daily_entry_player_date', columns: ['player_id', 'date'])]
class SleepDailyEntryDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: PlayerDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public PlayerDataModel $player;

    /**
     * Wake-up date (e.g. a sleep from May 5 23:00 to May 6 07:00 belongs to May 6). The
     * uniqueness constraint on (`player`, `date`) enforces "one sleep entry per night".
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public \DateTimeImmutable $date;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $bedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $wakeAt;

    /**
     * Auto-derived: `floor((wakeAt − bedAt) / 60)`. Computed by `SleepDurationEvaluator` and
     * applied from `SleepDailyEntryPersister` before `doCreate` / `doUpdate`. Default 0 is a
     * placeholder — the persister always overwrites before the row hits MySQL.
     */
    #[ORM\Column(type: Types::INTEGER)]
    public int $durationMinutes = 0;

    /** Subjective night quality, 1–5. Nullable — the player can log a night without scoring it. */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    public ?int $quality = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        PlayerDataModel $player,
        \DateTimeImmutable $date,
        \DateTimeImmutable $bedAt,
        \DateTimeImmutable $wakeAt,
    ) {
        $this->player = $player;
        $this->date = $date;
        $this->bedAt = $bedAt;
        $this->wakeAt = $wakeAt;
    }
}
