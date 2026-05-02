<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training\Workout;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'exercise_set')]
class ExerciseSetDataModel implements DataModelInterface, OwnedByPlayerInterface
{
    public PlayerDataModel $player {
        get => $this->exercise->workout->player;
    }

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\ManyToOne(targetEntity: ExerciseDataModel::class, inversedBy: 'exerciseSets')]
    #[ORM\JoinColumn(nullable: false)]
    public ExerciseDataModel $exercise;

    #[ORM\Column(type: Types::INTEGER)]
    public int $position = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $plannedReps = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $achievedReps = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    public ?string $plannedWeight = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    public ?string $achievedWeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $plannedDurationSeconds = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $achievedDurationSeconds = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    public ?string $plannedDistanceMeters = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    public ?string $achievedDistanceMeters = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    public ?string $plannedInclinePercent = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    public ?string $achievedInclinePercent = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    public ?string $plannedInclineMeters = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    public ?string $achievedInclineMeters = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isComplete = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        ExerciseDataModel $exercise,
        int $position,
    ) {
        $this->exercise = $exercise;
        $this->position = $position;
    }
}
