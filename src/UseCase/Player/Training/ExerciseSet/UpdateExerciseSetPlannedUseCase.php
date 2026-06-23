<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetPlannedDataInput;
use App\Domain\DTO\DataOutput\Player\Training\ExerciseSet\ExerciseSetDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\ExerciseSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseSetProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\ExerciseSet\UpdateExerciseSetPlannedValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateExerciseSetPlannedUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateExerciseSetPlannedValidator $updateExerciseSetPlannedValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly ExerciseSetProviderGateway $exerciseSetProvider,
        private readonly ExerciseSetPersisterGateway $exerciseSetPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateExerciseSetPlannedDataInput $input
     */
    public function execute(DataInputInterface $input): ExerciseSetDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $set = $this->exerciseSetProvider->findOneByIdForPlayerAction($input->exerciseSetId, $player);
        if (null === $set) {
            throw new EntityNotFoundException(sprintf('Exercise set "%s" not found.', $input->exerciseSetId));
        }

        $this->updateExerciseSetPlannedValidator->validate($player, $input, $set);

        $set->plannedReps = $input->plannedReps;
        $set->plannedWeight = $input->plannedWeight;
        $set->plannedDurationSeconds = $input->plannedDurationSeconds;
        $set->plannedDistanceMeters = $input->plannedDistanceMeters;
        $set->plannedInclinePercent = $input->plannedInclinePercent;
        $set->plannedInclineMeters = $input->plannedInclineMeters;

        $this->exerciseSetPersister->update($set);

        return $this->mapper->map($set, ExerciseSetDataOutput::class);
    }
}
