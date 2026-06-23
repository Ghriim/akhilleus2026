<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer;

final readonly class DateDataTransformer
{
    /**
     * Formats a nullable date as an ISO 8601 / RFC 3339 string (the DataOutput
     * date convention). Used as an ObjectMapper transform callable, so it also
     * tolerates the extra ($source, $target) arguments the mapper passes.
     */
    public static function toAtom(?\DateTimeInterface $date): ?string
    {
        return $date?->format(\DateTimeInterface::ATOM);
    }
}
