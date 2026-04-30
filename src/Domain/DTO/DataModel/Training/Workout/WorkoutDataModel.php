<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training\Workout;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'workout')]
class WorkoutDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: PlayerDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public PlayerDataModel $player;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $dateStart = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $dateEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $plannedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        PlayerDataModel $player,
        string $status,
    ) {
        $this->id = $id;
        $this->player = $player;
        $this->status = $status;
    }
}
