<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Muscle;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Muscle\UpdateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class UpdateMuscleValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'UPDATE_EQUIPMENT_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private MuscleProviderGateway $muscleProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(object $input): void
    {
        if (false === $input instanceof UpdateMuscleDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', UpdateMuscleDataInput::class, $input::class));
        }

        $violations = [];
        if ('' === trim($input->label)) {
            $violations['label'][] = 'Label must not be empty.';
        } else {
            $slug = $this->stringDataTransformer->slugify($input->label);
            $existing = $this->muscleProviderGateway->findOneBySlugForUniqueness($slug);
            if (null !== $existing && $existing->id !== $input->id) {
                $violations['label'][] = 'Another muscle already uses this label.';
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Muscle update data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
