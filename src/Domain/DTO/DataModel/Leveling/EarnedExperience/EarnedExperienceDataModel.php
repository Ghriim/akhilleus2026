<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Leveling\EarnedExperience;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An immutable XP grant: a workout completion or a claimed quest reward. Entries stay
 * `isLocked = false` until the nightly leveling cron (Phase 5/6) folds them into the player's
 * level/currentXp and locks them. `sourceType` is one of `EarnedExperienceSourceTypeRegistry`;
 * `sourceId` is the ULID of the originating workout or quest progression.
 */
#[ORM\Entity]
#[ORM\Table(name: 'earned_experience')]
class EarnedExperienceDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: PlayerDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public PlayerDataModel $player;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $label;

    #[ORM\Column(type: Types::INTEGER)]
    public int $amount;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $earnedAt;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $sourceType;

    #[ORM\Column(type: Types::STRING, length: 26)]
    public string $sourceId;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isLocked = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        PlayerDataModel $player,
        string $label,
        int $amount,
        \DateTimeImmutable $earnedAt,
        string $sourceType,
        string $sourceId,
    ) {
        $this->player = $player;
        $this->label = $label;
        $this->amount = $amount;
        $this->earnedAt = $earnedAt;
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
    }
}
