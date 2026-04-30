<?php

declare(strict_types=1);

namespace App\UseCase\Admin\Training\Movement;

use App\Domain\DTO\DataInput\Admin\Training\Movement\DeleteMovementDataInput;
use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataOutput\Admin\Training\Movement\DeleteMovementDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Validator\Admin\Training\Movement\DeleteMovementValidator;
use App\UseCase\AbstractLoggedUserUseCase;

final class DeleteMovementUseCase extends AbstractLoggedUserUseCase
{
    public function __construct(
        private readonly DeleteMovementValidator $deleteMovementValidator,
        private readonly MovementProviderGateway $movementProvider,
        private readonly MovementPersisterGateway $movementPersister,
    ) {
        parent::__construct($deleteMovementValidator);
    }

    public function execute(DeleteMovementDataInput|DataInputInterface $input): DeleteMovementDataOutput
    {
        if (false === $input instanceof DeleteMovementDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', DeleteMovementDataInput::class, $input::class));
        }

        $this->deleteMovementValidator->validate($input);

        $movement = $this->movementProvider->findOneForAdminDetails($input->id);
        if (null === $movement) {
            throw new EntityNotFoundException(sprintf('Movement "%s" not found.', $input->id));
        }

        $this->movementPersister->delete($movement);

        return new DeleteMovementDataOutput($input->id);
    }
}
