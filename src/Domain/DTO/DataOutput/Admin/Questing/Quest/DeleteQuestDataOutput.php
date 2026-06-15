<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\Admin\Questing\Quest;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class DeleteQuestDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $deletedId,
    ) {
    }
}
