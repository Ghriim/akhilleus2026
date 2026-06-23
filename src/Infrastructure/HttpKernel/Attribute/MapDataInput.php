<?php

declare(strict_types=1);

namespace App\Infrastructure\HttpKernel\Attribute;

use App\Infrastructure\HttpKernel\ArgumentResolver\DataInputValueResolver;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class MapDataInput extends ValueResolver
{
    public function __construct(
        string $resolver = DataInputValueResolver::class,
    ) {
        parent::__construct($resolver);
    }
}
