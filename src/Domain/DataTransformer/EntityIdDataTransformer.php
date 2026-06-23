<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer;

use App\Domain\DTO\DataModel\DataModelInterface;

final readonly class EntityIdDataTransformer
{
    /**
     * Projects a (possibly null) related DataModel down to its `id`, or null when
     * the relation is unset. Used as an ObjectMapper transform callable on a `?string`
     * id target whose source is a nullable to-one relation, so it also tolerates the
     * extra ($source, $target) arguments the mapper passes.
     */
    public static function idOrNull(?DataModelInterface $entity): ?string
    {
        return $entity?->id;
    }
}
