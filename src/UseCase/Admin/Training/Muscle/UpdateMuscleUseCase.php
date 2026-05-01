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
    }

    /**
     * @param UpdateMuscleDataInput $input
     */
    public function execute(DataInputInterface $input): MuscleDataOutput
    {
        $muscle = $this->muscleProvider->findOneForAdminDetails($input->id);
        if (null === $muscle) {
            throw new EntityNotFoundException(sprintf('Muscle "%s" not found.', $input->id));
        }

        $this->updateMuscleValidator->validate($input, $muscle);

        $muscle->label = $input->label;
        $this->musclePersister->update($muscle);

        return new MuscleDataOutput($muscle->id, $muscle->slug, $muscle->label);
    }
}
