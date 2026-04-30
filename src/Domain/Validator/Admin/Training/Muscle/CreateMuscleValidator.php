<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedUserValidator;

final readonly class CreateMuscleValidator extends AbstractLoggedUserValidator
{
    public const string ERROR_CODE = 'CREATE_EQUIPMENT_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private MuscleProviderGateway $muscleProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(object $input): void
    {
        if (false === $input instanceof CreateMuscleDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', CreateMuscleDataInput::class, $input::class));
        }

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
