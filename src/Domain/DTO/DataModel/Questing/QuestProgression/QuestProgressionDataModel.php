<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Questing\QuestProgression;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One player's progress on one quest for one period. `startDate`/`endDate` bound the period
 * (both null for a `UNIQUE` quest). `currentValue` is recomputed by `QuestProgressionEvaluator`
 * for automatic quests (numeric-string `DECIMAL` per the v0 convention). The
 * `(quest_id, player_id, start_date)` unique constraint relies on MySQL's NULL-distinct semantics:
 * a `UNIQUE` quest (null `start_date`) keeps exactly one row per player, while recurring quests get
 * one row per period.
 */
#[ORM\Entity]
#[ORM\Table(name: 'quest_progression')]
#[ORM\UniqueConstraint(name: 'uniq_quest_progression_quest_player_start', columns: ['quest_id', 'player_id', 'start_date'])]
class QuestProgressionDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: QuestDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public QuestDataModel $quest;

    #[ORM\ManyToOne(targetEntity: PlayerDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public PlayerDataModel $player;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $completionDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $claimedDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    public ?string $currentValue = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        QuestDataModel $quest,
        PlayerDataModel $player,
        string $status,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
        ?string $currentValue = null,
    ) {
        $this->quest = $quest;
        $this->player = $player;
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->currentValue = $currentValue;
    }
}
