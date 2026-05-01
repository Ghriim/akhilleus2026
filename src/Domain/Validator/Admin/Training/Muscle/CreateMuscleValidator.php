<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class CreateMuscleValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'CREATE_MUSCLE_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private MuscleProviderGateway $muscleProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(CreateMuscleDataInput $input): void
    {
        $violations = [];
        if ('' === trim($input->label)) {
            $violations['label'][] = 'Label must not be empty.';
        } else {
            $slug = $this->stringDataTransformer->slugify($input->label);
            if (null !== $this->muscleProviderGateway->findOneBySlugForUniqueness($slug)) {
                $violations['label'][] = 'Another muscle already uses this label.';
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Muscle creation data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
