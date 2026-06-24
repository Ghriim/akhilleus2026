<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class UpdateFrontThemeDataInput implements DataInputInterface
{
    /**
     * @param ?string $imageSourcePath absolute path to the validated uploaded file (null = keep current)
     * @param ?string $imageExtension  extension (no dot) of the uploaded file, or null
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?string $imageSourcePath = null,
        public ?string $imageExtension = null,
    ) {
    }
}
