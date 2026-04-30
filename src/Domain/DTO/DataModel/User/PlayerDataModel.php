<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\User;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'player')]
class PlayerDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\OneToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(unique: true, nullable: false)]
    public UserDataModel $user;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $displayName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        UserDataModel $user,
        string $displayName,
    ) {
        $this->user = $user;
        $this->displayName = $displayName;
    }
}
