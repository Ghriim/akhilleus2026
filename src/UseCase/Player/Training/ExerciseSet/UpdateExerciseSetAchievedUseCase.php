<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetAchievedDataInput;
use App\Domain\DTO\DataOutput\Player\Training\ExerciseSet\ExerciseSetDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\ExerciseSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseSetProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\ExerciseSetCompletionEvaluator;
use App\Domain\Validator\Player\Training\ExerciseSet\UpdateExerciseSetAchievedValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateExerciseSetAchievedUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateExerciseSetAchievedValidator $updateExerciseSetAchievedValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly ExerciseSetProviderGateway $exerciseSetProvider,
        private readonly ExerciseSetPersisterGateway $exerciseSetPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateExerciseSetAchievedDataInput $input
     */
    public function execute(DataInputInterface $input): ExerciseSetDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $set = $this->exerciseSetProvider->findOneByIdForPlayerAction($input->exerciseSetId, $player);
        if (null === $set) {
            throw new EntityNotFoundException(sprintf('Exercise set "%s" not found.', $input->exerciseSetId));
        }

        $this->updateExerciseSetAchievedValidator->validate($player, $input, $set);

        $set->achievedReps = $input->achievedReps;
        $set->achievedWeight = $input->achievedWeight;
        $set->achievedDurationSeconds = $input->achievedDurationSeconds;
        $set->achievedDistanceMeters = $input->achievedDistanceMeters;
        $set->achievedInclinePercent = $input->achievedInclinePercent;
        $set->achievedInclineMeters = $input->achievedInclineMeters;
        $set->isComplete = ExerciseSetCompletionEvaluator::isComplete($set, $set->exercise->movement);

        $this->exerciseSetPersister->update($set);

        return $this->mapper->map($set, ExerciseSetDataOutput::class);
    }
}
