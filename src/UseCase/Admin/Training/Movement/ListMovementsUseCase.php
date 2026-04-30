<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\ListMovementsDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Movement\MovementListItemDataOutput;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Validator\Admin\Training\Movement\ListMovementsValidator;
use App\UseCase\AbstractPublicUseCase;

final class ListMovementsUseCase extends AbstractPublicUseCase
{
    public function __construct(
        private readonly ListMovementsValidator $listMovementsValidator,
        private readonly MovementProviderGateway $movementProvider,
    ) {
        parent::__construct($listMovementsValidator);
    }

    /**
     * @return list<MovementListItemDataOutput>
     */
    public function execute(ListMovementsDataInput|DataInputInterface $input): array
    {
        if (false === $input instanceof ListMovementsDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', ListMovementsDataInput::class, $input::class));
        }

        $this->listMovementsValidator->validate($input);

        $movements = $this->movementProvider->findAllForAdminList($input->sort, $input->direction);

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
