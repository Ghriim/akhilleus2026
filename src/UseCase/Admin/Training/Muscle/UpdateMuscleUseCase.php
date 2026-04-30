<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\UpdateMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Validator\Admin\Training\Muscle\UpdateMuscleValidator;
use App\UseCase\AbstractLoggedAdminUseCase;

final class UpdateMuscleUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly UpdateMuscleValidator $updateMuscleValidator,
        private readonly MuscleProviderGateway $muscleProvider,
        private readonly MusclePersisterGateway $musclePersister,
    ) {
        parent::__construct($updateMuscleValidator);
    }

    public function execute(UpdateMuscleDataInput|DataInputInterface $input): MuscleDataOutput
    {
        if (false === $input instanceof UpdateMuscleDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', UpdateMuscleDataInput::class, $input::class));
        }

        $this->updateMuscleValidator->validate($input);

        $muscle = $this->muscleProvider->findOneForAdminDetails($input->id);
        if (null === $muscle) {
            throw new EntityNotFoundException(sprintf('Muscle "%s" not found.', $input->id));
        }

        $muscle->label = $input->label;
        $this->musclePersister->update($muscle);

        return new MuscleDataOutput($muscle->id, $muscle->slug, $muscle->label);
    }
}
