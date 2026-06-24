<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\User\FrontTheme;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class DeleteFrontThemeDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
