<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class GetQuestDetailsDataInput implements DataInputInterface
{
    public function __construct(
        public string $id,
    ) {
    }
}
