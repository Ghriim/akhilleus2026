<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\User;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'front_theme')]
class FrontThemeDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    public string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    /** Stored preview-image filename under public/uploads/front-themes (null = no preview). */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $imageFilename = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
