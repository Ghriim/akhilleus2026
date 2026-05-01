<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\GetMuscleDetailsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\UseCase\AbstractPublicUseCase;

final class GetMuscleDetailsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly MuscleProviderGateway $muscleProvider,
    ) {
    }

    /**
     * @param GetMuscleDetailsDataInput $input
     */
    public function execute(DataInputInterface $input): MuscleDataOutput
    {
        $muscle = $this->muscleProvider->findOneForAdminDetails($input->id);
        if (null === $muscle) {
            throw new EntityNotFoundException(sprintf('Muscle "%s" not found.', $input->id));
        }

        return new MuscleDataOutput($muscle->id, $muscle->slug, $muscle->label);
    }
}
