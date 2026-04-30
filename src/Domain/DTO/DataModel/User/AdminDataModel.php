<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\User;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\Registry\User\AdminStatusRegistry;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'admin')]
class AdminDataModel implements DataModelInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ORM\OneToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    public UserDataModel $user;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $firstName;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public string $lastName;

    #[ORM\Column(type: Types::STRING, length: 150)]
    public string $jobTitle;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public \DateTimeImmutable $hiredAt;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public string $status = AdminStatusRegistry::ACTIVE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        UserDataModel $user,
        string $firstName,
        string $lastName,
        string $jobTitle,
        \DateTimeImmutable $hiredAt,
    ) {
        $this->user = $user;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->jobTitle = $jobTitle;
        $this->hiredAt = $hiredAt;
    }
}
