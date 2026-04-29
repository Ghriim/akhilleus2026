<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Exercise;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Workout\WorkoutDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'exercise')]
class ExerciseDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: WorkoutDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
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

    public function __construct(
        string $id,
        WorkoutDataModel $workout,
        MovementDataModel $movement,
        int $position,
        int $restDurationSeconds = 0,
    ) {
        $this->id = $id;
        $this->workout = $workout;
        $this->movement = $movement;
        $this->position = $position;
        $this->restDurationSeconds = $restDurationSeconds;
    }
}
