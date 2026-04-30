<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Movement;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Movement\UpdateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class UpdateMovementValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'UPDATE_MOVEMENT_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private MovementProviderGateway $movementProviderGateway,
        private MuscleProviderGateway $muscleProviderGateway,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(object $input): void
    {
        if (false === $input instanceof UpdateMovementDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', UpdateMovementDataInput::class, $input::class));
        }

        $violations = [];

        if ('' === trim($input->label)) {
            $violations['label'][] = 'Label must not be empty.';
        } else {
            $slug = $this->stringDataTransformer->slugify($input->label);
            $existing = $this->movementProviderGateway->findOneBySlugForUniqueness($slug);
            if (null !== $existing && $existing->id !== $input->id) {
                $violations['label'][] = 'Another movement already uses this label.';
            }
        }

        if (null === $this->muscleProviderGateway->findOneForAdminDetails($input->mainMuscleId)) {
            $violations['mainMuscleId'][] = sprintf('Muscle "%s" does not exist.', $input->mainMuscleId);
        }

        foreach ($input->secondaryMuscleIds as $muscleId) {
            if (null === $this->muscleProviderGateway->findOneForAdminDetails($muscleId)) {
                $violations['secondaryMuscleIds'][] = sprintf('Muscle "%s" does not exist.', $muscleId);
            }
        }

        foreach ($input->equipmentIds as $equipmentId) {
            if (null === $this->equipmentProviderGateway->findOneForAdminDetails($equipmentId)) {
                $violations['equipmentIds'][] = sprintf('Equipment "%s" does not exist.', $equipmentId);
            }
        }

        $hasAtLeastOneTrackingField = $input->tracksRepetitions
            || $input->tracksWeight
            || $input->tracksDuration
            || $input->tracksDistance
            || $input->tracksInclinePercent
            || $input->tracksInclineMeters;

        if (false === $hasAtLeastOneTrackingField) {
            $violations['tracking'][] = 'At least one tracking field must be enabled.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Movement update data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
