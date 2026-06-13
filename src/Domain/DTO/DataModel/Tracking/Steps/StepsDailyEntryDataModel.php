<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Tracking\Steps;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'steps_daily_entry')]
#[ORM\UniqueConstraint(name: 'uniq_steps_daily_entry_player_date', columns: ['player_id', 'date'])]
class StepsDailyEntryDataModel implements DataModelInterface, OwnedByPlayerInterface
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

    #[ORM\Column(type: Types::INTEGER)]
    public int $count;

    /**
     * Daily step goal, snapshotted from the player's `dailyStepsTarget` at create time, then
     * editable per-day. Editing the player's global default afterwards does not change this value.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 5000])]
    public int $target;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        PlayerDataModel $player,
        \DateTimeImmutable $date,
        int $count,
        int $target,
    ) {
        $this->player = $player;
        $this->date = $date;
        $this->count = $count;
        $this->target = $target;
    }
}
