<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training\Workout;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'personal_best')]
#[ORM\UniqueConstraint(name: 'uniq_personal_best_player_movement_type', columns: ['player_id', 'movement_id', 'type'])]
class PersonalBestDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: PlayerDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public PlayerDataModel $player;

    #[ORM\ManyToOne(targetEntity: MovementDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public MovementDataModel $movement;

    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $type;

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 4)]
    public string $value;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $achievedAt;

    #[ORM\ManyToOne(targetEntity: WorkoutDataModel::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?WorkoutDataModel $workout = null;

    #[ORM\ManyToOne(targetEntity: ExerciseSetDataModel::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?ExerciseSetDataModel $exerciseSet = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    /**
     * @param numeric-string $value
     */
    public function __construct(
        string $id,
        PlayerDataModel $player,
        MovementDataModel $movement,
        string $type,
        string $value,
        \DateTimeImmutable $achievedAt,
    ) {
        $this->id = $id;
        $this->player = $player;
        $this->movement = $movement;
        $this->type = $type;
        $this->value = $value;
        $this->achievedAt = $achievedAt;
    }
}
