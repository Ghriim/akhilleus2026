<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Leveling\EarnedExperience;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class EarnedExperienceDataOutput implements DataOutputInterface
{
    public string $id;
    public string $label;
    public int $amount;
    #[Map(transform: [DateDataTransformer::class, 'toAtom'])]
    public ?string $earnedAt;
    public string $sourceType;
    public string $sourceId;
    public bool $isLocked;
}
