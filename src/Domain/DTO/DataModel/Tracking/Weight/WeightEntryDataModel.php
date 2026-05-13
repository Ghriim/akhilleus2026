<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Tracking\Weight;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'weight_entry')]
#[ORM\UniqueConstraint(name: 'uniq_weight_entry_player_date', columns: ['player_id', 'date'])]
class WeightEntryDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: PlayerDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public PlayerDataModel $player;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $loggedAt;

    /**
     * Auto-derived from `loggedAt` (`->setTime(0,0,0)`) — used to enforce the one-weight-per-day
     * uniqueness constraint. Initialised in the constructor to satisfy the non-nullable column;
     * the matching persister recomputes it on update so it stays in sync if `loggedAt` changes.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public \DateTimeImmutable $date;

    #[ORM\Column(type: Types::INTEGER)]
    public int $valueGrams;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        PlayerDataModel $player,
        \DateTimeImmutable $loggedAt,
        int $valueGrams,
    ) {
        $this->player = $player;
        $this->loggedAt = $loggedAt;
        $this->valueGrams = $valueGrams;
        $this->date = $loggedAt->setTime(0, 0, 0);
    }
}
