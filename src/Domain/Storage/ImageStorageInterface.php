<?php

declare(strict_types=1);

namespace App\Domain\Storage;

/**
 * Persists preview images outside the Domain. Implemented in Infrastructure; only primitives cross
 * the boundary so the Domain stays free of Symfony / filesystem types.
 */
interface ImageStorageInterface
{
    /**
     * Stores the file found at $sourcePath and returns the generated stored filename.
     *
     * @param string $extension file extension without a leading dot (e.g. "png", "jpg")
     */
    public function store(string $sourcePath, string $extension): string;

    public function remove(?string $filename): void;

    public function publicUrl(?string $filename): ?string;
}
