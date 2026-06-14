<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Leveling\LevelingConfig;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Singleton holding admin-editable leveling parameters. Exactly one row, keyed by the well-known
 * fixed id below — seeded by the Phase 3.10 migration and loaded via `getSingleton()` (Phase 3.4).
 * `xpPerWorkoutMinute` is the XP granted per minute of a completed workout (≥ 50).
 */
#[ORM\Entity]
#[ORM\Table(name: 'leveling_config')]
class LevelingConfigDataModel implements DataModelInterface
{
    public const string LEVELING_CONFIG_ID = '01000000000000000000000000';

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 26)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    public string $id = self::LEVELING_CONFIG_ID;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 25])]
    public int $xpPerWorkoutMinute = 25;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct(int $xpPerWorkoutMinute = 25)
    {
        $this->xpPerWorkoutMinute = $xpPerWorkoutMinute;
    }
}
