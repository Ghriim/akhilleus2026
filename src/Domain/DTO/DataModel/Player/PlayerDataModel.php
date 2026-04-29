<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Player;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\User\UserDataModel;
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
    #[ORM\JoinColumn(nullable: false, unique: true)]
    public UserDataModel $user;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $displayName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        UserDataModel $user,
        string $displayName,
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->displayName = $displayName;
    }
}
