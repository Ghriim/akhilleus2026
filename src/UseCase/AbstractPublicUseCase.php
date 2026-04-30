<?php

declare(strict_types=1);

namespace App\UseCase;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\DataOutputInterface;
use App\Domain\Validator\DomainValidatorInterface;

abstract class AbstractPublicUseCase implements UseCaseInterface
{
    public function __construct(
        protected readonly DomainValidatorInterface $validator,
    ) {
    }

    /**
     * @return DataOutputInterface|list<DataOutputInterface>
     */
    abstract public function execute(DataInputInterface $input): DataOutputInterface|array;
}
