<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\DeleteMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\DeleteMuscleDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\UseCase\AbstractLoggedAdminUseCase;

final class DeleteMuscleUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly MuscleProviderGateway $muscleProvider,
        private readonly MusclePersisterGateway $musclePersister,
    ) {
    }

    /**
     * @param DeleteMuscleDataInput $input
     */
    public function execute(DataInputInterface $input): DeleteMuscleDataOutput
    {
        $muscle = $this->muscleProvider->findOneForAdminDetails($input->id);
        if (null === $muscle) {
            throw new EntityNotFoundException(sprintf('Muscle "%s" not found.', $input->id));
        }

        $this->musclePersister->delete($muscle);

        return new DeleteMuscleDataOutput($input->id);
    }
}
