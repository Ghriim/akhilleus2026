<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\Admin\Training\Muscle\ListMusclesDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Validator\Admin\Training\Muscle\ListMusclesValidator;
use App\UseCase\AbstractPublicUseCase;

final class ListMusclesUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly ListMusclesValidator $listMusclesValidator,
        private readonly MuscleProviderGateway $muscleProvider,
    ) {
    }

    /**
     * @param ListMusclesDataInput $input
     *
     * @return list<MuscleListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $this->listMusclesValidator->validate($input);

        $muscles = $this->muscleProvider->findAllForAdminList($input->sort, $input->direction);

        return array_map(
            static fn ($muscle) => new MuscleListItemDataOutput(
                $muscle->id,
                $muscle->slug,
                $muscle->label,
            ),
            $muscles,
        );
    }
}
