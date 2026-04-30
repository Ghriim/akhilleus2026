<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Muscle;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Muscle\MuscleListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Muscle\MuscleProviderGateway;
use App\Domain\Validator\EmptyDomainValidator;
use App\UseCase\AbstractPublicUseCase;

final class ListMusclesUseCase extends AbstractPublicUseCase
{
    public function __construct(
        EmptyDomainValidator $validator,
        private readonly MuscleProviderGateway $muscleProvider,
    ) {
        parent::__construct($validator);
    }

    /**
     * @return list<MuscleListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $muscles = $this->muscleProvider->findAllForAdminList();

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
