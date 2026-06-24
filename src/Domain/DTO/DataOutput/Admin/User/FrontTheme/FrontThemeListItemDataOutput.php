<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\User\FrontTheme;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final class FrontThemeListItemDataOutput implements DataOutputInterface
{
    public string $id;
    public string $name;
    public ?string $description;
    /** Computed by the use case from the stored filename via ImageStorageInterface::publicUrl(). */
    public ?string $imagePreviewUrl;
}
