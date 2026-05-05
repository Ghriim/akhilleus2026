<?php

declare(strict_types=1);

namespace App\Domain\Validator\Admin\Training\Movement;

use App\Domain\DataTransformer\StringDataTransformerInterface;
use App\Domain\DTO\DataInput\Admin\Training\Movement\CreateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Equipment\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Security\LoggedUserResolverInterface;
use App\Domain\Validator\AbstractLoggedAdminValidator;

final readonly class CreateMovementValidator extends AbstractLoggedAdminValidator
{
    public const string ERROR_CODE = 'CREATE_MOVEMENT_VALIDATION_FAILED';

    public function __construct(
        LoggedUserResolverInterface $loggedUserResolver,
        private MovementProviderGateway $movementProviderGateway,
        private MuscleProviderGateway $muscleProviderGateway,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private StringDataTransformerInterface $stringDataTransformer,
    ) {
        parent::__construct($loggedUserResolver);
    }

    public function validate(CreateMovementDataInput $input): void
    {
        $violations = [];

        if ('' === trim($input->label)) {
            $violations['label'][] = 'Label must not be empty.';
        } else {
            $slug = $this->stringDataTransformer->slugify($input->label);
            if (null !== $this->movementProviderGateway->findOneBySlugForUniqueness($slug)) {
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

        if (null !== $input->videoLink && false === filter_var($input->videoLink, FILTER_VALIDATE_URL)) {
            $violations['videoLink'][] = 'Video link must be a valid URL.';
        }

        if (null !== $input->gifLink && false === filter_var($input->gifLink, FILTER_VALIDATE_URL)) {
            $violations['gifLink'][] = 'GIF link must be a valid URL.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Movement creation data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
