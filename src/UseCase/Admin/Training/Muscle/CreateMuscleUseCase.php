<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleDataOutput;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Validator\Admin\Training\Muscle\CreateMuscleValidator;
use App\UseCase\AbstractLoggedUserUseCase;

final class CreateMuscleUseCase extends AbstractLoggedUserUseCase
{
    public function __construct(
        private readonly CreateMuscleValidator $createMuscleValidator,
        private readonly MusclePersisterGateway $musclePersister,
    ) {
        parent::__construct($createMuscleValidator);
    }

    public function execute(CreateMuscleDataInput|DataInputInterface $input): MuscleDataOutput
    {
        if (false === $input instanceof CreateMuscleDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', CreateMuscleDataInput::class, $input::class));
        }

        $this->createMuscleValidator->validate($input);

        $muscle = $this->musclePersister->create(new MuscleDataModel($input->label));

        return new MuscleDataOutput($muscle->id, $muscle->slug, $muscle->label);
    }
}
