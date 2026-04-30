<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\User;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\Registry\User\UserRoleRegistry;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class UserDataModel implements DataModelInterface, UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id;

    /** @var non-empty-string */
    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    public string $email;

    public ?string $plainPassword = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $password;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    public array $roles;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    /**
     * @param non-empty-string $email
     * @param list<string>     $roles
     */
    public function __construct(
        string $id,
        string $email,
        string $plainPassword,
        array $roles = [UserRoleRegistry::ROLE_PLAYER],
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->plainPassword = $plainPassword;
        $this->roles = $roles;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }
}
