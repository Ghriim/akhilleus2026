<?php

declare(strict_types=1);

namespace App\Domain\Validator;

use App\Domain\Exception\ValidationException;

interface DomainValidatorInterface
{
    /**
     * @throws ValidationException
     */
    public function validate(object $input): void;
}
