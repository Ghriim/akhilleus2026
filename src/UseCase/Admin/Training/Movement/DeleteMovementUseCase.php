<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\DeleteMovementDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Movement\DeleteMovementDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\UseCase\AbstractLoggedAdminUseCase;

final class DeleteMovementUseCase extends AbstractLoggedAdminUseCase
{
    public function __construct(
        private readonly MovementProviderGateway $movementProvider,
        private readonly MovementPersisterGateway $movementPersister,
    ) {
    }

    /**
     * @param DeleteMovementDataInput $input
     */
    public function execute(DataInputInterface $input): DeleteMovementDataOutput
    {
        $movement = $this->movementProvider->findOneForAdminDetails($input->id);
        if (null === $movement) {
            throw new EntityNotFoundException(sprintf('Movement "%s" not found.', $input->id));
        }

        $this->movementPersister->delete($movement);

        return new DeleteMovementDataOutput($input->id);
    }
}
