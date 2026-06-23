<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Exercise;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Exercise\RemoveMovementFromWorkoutDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\ExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\RemoveMovementFromWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class RemoveMovementFromWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly RemoveMovementFromWorkoutValidator $removeMovementFromWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly ExerciseProviderGateway $exerciseProvider,
        private readonly ExercisePersisterGateway $exercisePersister,
    ) {
    }

    /**
     * @param RemoveMovementFromWorkoutDataInput $input
     */
    public function execute(DataInputInterface $input): null
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $exercise = $this->exerciseProvider->findOneByIdForPlayerAction($input->exerciseId, $player);
        if (null === $exercise) {
            throw new EntityNotFoundException(sprintf('Exercise "%s" not found.', $input->exerciseId));
        }

        $this->removeMovementFromWorkoutValidator->validate($player, $input, $exercise);

        $this->exercisePersister->delete($exercise);

        return null;
    }
}
