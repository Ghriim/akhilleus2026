<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training\Workout;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'exercise')]
class ExerciseDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    public PlayerDataModel $player {
        get => $this->workout->player;
    }

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutDataModel::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public WorkoutDataModel $workout;

    #[ORM\ManyToOne(targetEntity: MovementDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public MovementDataModel $movement;

    #[ORM\Column(type: Types::INTEGER)]
    public int $restDurationSeconds = 0;

    #[ORM\Column(type: Types::INTEGER)]
    public int $position = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ExerciseSetDataModel> */
    #[ORM\OneToMany(targetEntity: ExerciseSetDataModel::class, mappedBy: 'exercise', orphanRemoval: true)]
    public Collection $exerciseSets;

    public function __construct(
        WorkoutDataModel $workout,
        MovementDataModel $movement,
        int $position,
        int $restDurationSeconds = 0,
    ) {
        $this->workout = $workout;
        $this->movement = $movement;
        $this->position = $position;
        $this->restDurationSeconds = $restDurationSeconds;
        $this->exerciseSets = new ArrayCollection();
    }
}
