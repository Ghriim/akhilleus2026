<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class ListMusclesDataInput implements DataInputInterface
{
    /** @var list<string> */
    public const array ALLOWED_SORTS = ['label'];

    public function __construct(
        public string $sort = 'label',
        public string $direction = 'ASC',
    ) {
    }
}
