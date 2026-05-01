<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\CreateMuscleDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleDataOutput;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Validator\Admin\Training\Muscle\CreateMuscleValidator;
use App\UseCase\AbstractLoggedAdminUseCase;

final class CreateMuscleUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly CreateMuscleValidator $createMuscleValidator,
        private readonly MusclePersisterGateway $musclePersister,
    ) {
    }

    /**
     * @param CreateMuscleDataInput $input
     */
    public function execute(DataInputInterface $input): MuscleDataOutput
    {
        $this->createMuscleValidator->validate($input);

        $muscle = $this->musclePersister->create(new MuscleDataModel($input->label));

        return new MuscleDataOutput($muscle->id, $muscle->slug, $muscle->label);
    }
}
