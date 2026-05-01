<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\RemoveExerciseSetDataInput;
use App\Domain\DTO\DataOutput\Player\Training\ExerciseSet\RemoveExerciseSetDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\ExerciseSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseSetProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\ExerciseSet\RemoveExerciseSetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class RemoveExerciseSetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly RemoveExerciseSetValidator $removeExerciseSetValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly ExerciseSetProviderGateway $exerciseSetProvider,
        private readonly ExerciseSetPersisterGateway $exerciseSetPersister,
    ) {
    }

    /**
     * @param RemoveExerciseSetDataInput $input
     */
    public function execute(DataInputInterface $input): RemoveExerciseSetDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $set = $this->exerciseSetProvider->findOneByIdForPlayerAction($input->exerciseSetId, $player);
        if (null === $set) {
            throw new EntityNotFoundException(sprintf('Exercise set "%s" not found.', $input->exerciseSetId));
        }

        $this->removeExerciseSetValidator->validate($player, $input, $set);

        $this->exerciseSetPersister->delete($set);

        return new RemoveExerciseSetDataOutput($input->exerciseSetId);
    }
}
