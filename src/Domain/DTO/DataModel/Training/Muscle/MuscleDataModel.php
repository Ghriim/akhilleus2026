<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training\Muscle;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'muscle')]
class MuscleDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    public string $slug;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $label;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        string $label,
    ) {
        $this->label = $label;
    }
}
