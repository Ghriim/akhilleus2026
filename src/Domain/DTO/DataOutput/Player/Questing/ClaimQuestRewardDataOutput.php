<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Questing;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class ClaimQuestRewardDataOutput implements DataOutputInterface
{
    /** Mapped from the minted EarnedExperience, whose `sourceId` is the claimed progression's id. */
    #[Map(source: 'sourceId')]
    public string $progressionId;
    #[Map(source: 'id')]
    public string $earnedExperienceId;
    public int $amount;
}
