<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training\Movement;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'movement')]
class MovementDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\Column(type: Types::STRING, length: 80, unique: true)]
    public string $slug;

    #[ORM\Column(type: Types::STRING, length: 150)]
    public string $label;

    #[ORM\ManyToOne(targetEntity: MuscleDataModel::class)]
    #[ORM\JoinColumn(nullable: false)]
    public MuscleDataModel $mainMuscle;

    /** @var Collection<int, MuscleDataModel> */
    #[ORM\ManyToMany(targetEntity: MuscleDataModel::class)]
    #[ORM\JoinTable(name: 'movement_secondary_muscle')]
    #[ORM\JoinColumn(name: 'movement_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'muscle_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    public Collection $secondaryMuscles;

    /** @var Collection<int, EquipmentDataModel> */
    #[ORM\ManyToMany(targetEntity: EquipmentDataModel::class)]
    #[ORM\JoinTable(name: 'movement_equipment')]
    #[ORM\JoinColumn(name: 'movement_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'equipment_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    public Collection $equipments;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksRepetitions = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksWeight = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksDuration = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksDistance = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksInclinePercent = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksInclineMeters = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        string $label,
        MuscleDataModel $mainMuscle,
    ) {
        $this->label = $label;
        $this->mainMuscle = $mainMuscle;
        $this->secondaryMuscles = new ArrayCollection();
        $this->equipments = new ArrayCollection();
    }
}
