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

    /**
     * Player-wide nightly sleep goal in minutes. Initialised to 480 (8h) for new players (and
     * backfilled to 480 on existing rows by the migration). Editable by the player. Surfaced on
     * the profile so the sleep widget can render an objective + progress bar.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 480])]
    public int $dailySleepTargetMinutes = 480;

    /**
     * Optional player weight goal in grams (null = no goal set). Editable by the player and
     * surfaced on the profile so the weight widget can show the current/target gap.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $targetWeightGrams = null;

    /** Current player level (≥ 1). Bumped only by the nightly leveling cron (Phase 5/6). */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    public int $level = 1;

    /** Experience accumulated toward the next level (0 ≤ currentXp < xpToNextLevel). */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $currentXp = 0;

    /**
     * Marginal XP cost of the next level. Recomputed at registration by `LevelingCalculator`
     * (Phase 3.3) and on each level-up. Defaults to 4000 (= 1000×2²+0, the seeded bracket #1
     * cost for level 2) so existing rows backfill to a playable value.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 4000])]
    public int $xpToNextLevel = 4000;

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
