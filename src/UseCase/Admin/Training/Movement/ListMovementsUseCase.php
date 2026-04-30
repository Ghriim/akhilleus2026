<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Movement\MovementListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Validator\EmptyDomainValidator;
use App\UseCase\AbstractPublicUseCase;

final class ListMovementsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        EmptyDomainValidator $validator,
        private readonly MovementProviderGateway $movementProvider,
    ) {
        parent::__construct($validator);
    }

    /**
     * @return list<MovementListItemDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $movements = $this->movementProvider->findAllForAdminList();

        return array_map(
            static fn ($movement) => new MovementListItemDataOutput(
                $movement->id,
                $movement->slug,
                $movement->label,
                $movement->mainMuscle->slug,
            ),
            $movements,
        );
    }
}
