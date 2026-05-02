<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training\Workout;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'workout')]
class WorkoutDataModel implements DataModelInterface, OwnedByPlayerInterface
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

    /**
     * Human-friendly label such as "Monday Morning" / "Thursday Afternoon". Auto-derived by
     * `WorkoutPersister` on create from the workout's reference date (plannedAt for PLANNED,
     * dateStart for IN_PROGRESS, …) when left empty by the caller.
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $name = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $dateStart = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $dateEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $plannedAt = null;

    /**
     * Workout-level aggregates persisted at finish time (see `FinishWorkoutUseCase`):
     * - `duration`: seconds between dateStart and dateEnd.
     * - `volume`: sum of every set's `achievedWeight` (kg).
     * - `distance`: sum of every set's `achievedDistanceMeters` (m).
     * - `inclineMeters`: sum of every set's `achievedInclineMeters` (m).
     *
     * Stored to avoid recomputing on every read (history page, future analytics) and to keep
     * a stable historical snapshot if set values are ever edited after-the-fact.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $duration = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    public ?string $volume = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    public ?string $distance = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    public ?string $inclineMeters = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ExerciseDataModel> */
    #[ORM\OneToMany(targetEntity: ExerciseDataModel::class, mappedBy: 'workout', orphanRemoval: true)]
    public Collection $exercises;

    public function __construct(
        PlayerDataModel $player,
        string $status,
    ) {
        $this->player = $player;
        $this->status = $status;
        $this->exercises = new ArrayCollection();
    }
}
