<?php

declare(strict_types=1);

namespace App\Domain\DataTransformer;

interface StringDataTransformerInterface
{
    public function slugify(string $input): string;
}
