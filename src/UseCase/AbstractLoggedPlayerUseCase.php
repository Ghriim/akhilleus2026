<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\DataOutputInterface;

abstract class AbstractLoggedPlayerUseCase implements UseCaseInterface
{
    /**
     * @return DataOutputInterface|list<DataOutputInterface>|null
     */
    abstract public function execute(DataInputInterface $input): DataOutputInterface|array|null;
}
