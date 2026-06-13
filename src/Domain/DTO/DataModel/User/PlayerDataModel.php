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

    /**
     * Player-wide default daily hydration target in millilitres. Initialised to 1000 for new
     * players (and backfilled to 1000 on existing rows by the v1 migration). Can be edited by
     * the player from a profile section. Each `HydrationDailySummary` snapshots this value at
     * create time into its own `targetMl` column, so editing the global default afterwards
     * does not retroactively change past days' targets.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1000])]
    public int $dailyHydrationTargetMl = 1000;

    /**
     * Player-wide default daily step goal. Initialised to 5000 for new players (and backfilled
     * to 5000 on existing rows by the v1 migration). Can be edited by the player. Each
     * `StepsDailyEntry` snapshots this value at create time into its own `target` column, so
     * editing the global default afterwards does not retroactively change past days' goals.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 5000])]
    public int $dailyStepsTarget = 5000;

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
