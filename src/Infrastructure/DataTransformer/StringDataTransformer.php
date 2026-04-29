<?php

declare(strict_types=1);

namespace App\Infrastructure\DataTransformer;

use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class StringDataTransformer
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function slugify(string $input): string
    {
        return $this->slugger->slug($input)->lower()->toString();
    }
}
