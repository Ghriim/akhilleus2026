<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Player\Training\PersonalBest;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class MovementSummaryDataOutput implements DataOutputInterface
{
    public string $id;
    public string $slug;
    public string $label;
    #[Map(source: 'mainMuscle.slug')]
    public ?string $mainMuscleSlug;
}
