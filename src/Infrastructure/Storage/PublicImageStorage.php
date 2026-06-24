<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Storage\ImageStorageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Ulid;

/**
 * Stores preview images under public/uploads/front-themes so the web server serves them directly
 * at /uploads/front-themes/<file>. Single implementation of ImageStorageInterface, so Symfony
 * auto-aliases the interface to it (no services.yaml wiring needed).
 */
final readonly class PublicImageStorage implements ImageStorageInterface
{
    private const string SUBDIR = 'uploads/front-themes';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function store(string $sourcePath, string $extension): string
    {
        $directory = $this->absoluteDir();
        if (false === is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }

        $filename = (string) new Ulid().'.'.ltrim($extension, '.');
        copy($sourcePath, $directory.'/'.$filename);

        return $filename;
    }

    public function remove(?string $filename): void
    {
        if (null === $filename || '' === $filename) {
            return;
        }

        $path = $this->absoluteDir().'/'.$filename;
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function publicUrl(?string $filename): ?string
    {
        if (null === $filename || '' === $filename) {
            return null;
        }

        return '/'.self::SUBDIR.'/'.$filename;
    }

    private function absoluteDir(): string
    {
        return $this->projectDir.'/public/'.self::SUBDIR;
    }
}
