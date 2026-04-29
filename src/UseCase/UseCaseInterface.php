<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\DataOutputInterface;

interface UseCaseInterface
{
    /**
     * @return DataOutputInterface|list<DataOutputInterface>
     */
    public function execute(DataInputInterface $input): DataOutputInterface|array;
}
