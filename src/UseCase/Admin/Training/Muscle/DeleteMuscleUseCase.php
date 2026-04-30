<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\DeleteMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\DeleteMuscleDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Validator\Admin\Training\Muscle\DeleteMuscleValidator;
use App\UseCase\AbstractLoggedAdminUseCase;

final class DeleteMuscleUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly DeleteMuscleValidator $deleteMuscleValidator,
        private readonly MuscleProviderGateway $muscleProvider,
        private readonly MusclePersisterGateway $musclePersister,
    ) {
        parent::__construct($deleteMuscleValidator);
    }

    public function execute(DeleteMuscleDataInput|DataInputInterface $input): DeleteMuscleDataOutput
    {
        if (false === $input instanceof DeleteMuscleDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeleteMuscleDataInput::class, $input::class));
        }

        $this->deleteMuscleValidator->validate($input);

        $muscle = $this->muscleProvider->findOneForAdminDetails($input->id);
        if (null === $muscle) {
            throw new EntityNotFoundException(sprintf('Muscle "%s" not found.', $input->id));
        }

        $this->musclePersister->delete($muscle);

        return new DeleteMuscleDataOutput($input->id);
    }
}
