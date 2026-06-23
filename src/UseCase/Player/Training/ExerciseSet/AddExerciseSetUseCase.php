<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\AddExerciseSetDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataOutput\Player\Training\ExerciseSet\ExerciseSetDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\ExerciseSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseProviderGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseSetProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\ExerciseSetCompletionEvaluator;
use App\Domain\Validator\Player\Training\ExerciseSet\AddExerciseSetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class AddExerciseSetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly AddExerciseSetValidator $addExerciseSetValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly ExerciseProviderGateway $exerciseProvider,
        private readonly ExerciseSetProviderGateway $exerciseSetProvider,
        private readonly ExerciseSetPersisterGateway $exerciseSetPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param AddExerciseSetDataInput $input
     */
    public function execute(DataInputInterface $input): ExerciseSetDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $exercise = $this->exerciseProvider->findOneByIdForPlayerAction($input->exerciseId, $player);
        if (null === $exercise) {
            throw new EntityNotFoundException(sprintf('Exercise "%s" not found.', $input->exerciseId));
        }

        $this->addExerciseSetValidator->validate($player, $input, $exercise);

        $existing = $this->exerciseSetProvider->findAllByExerciseIdForPlayerAction($exercise->id, $player);
        $nextPosition = 0;
        foreach ($existing as $set) {
            if ($set->position >= $nextPosition) {
                $nextPosition = $set->position + 1;
            }
        }

        $created = new ExerciseSetDataModel($exercise, $nextPosition);
        $created->plannedReps = $input->plannedReps;
        $created->plannedWeight = $input->plannedWeight;
        $created->plannedDurationSeconds = $input->plannedDurationSeconds;
        $created->plannedDistanceMeters = $input->plannedDistanceMeters;
        $created->plannedInclinePercent = $input->plannedInclinePercent;
        $created->plannedInclineMeters = $input->plannedInclineMeters;
        $created->achievedReps = $input->achievedReps;
        $created->achievedWeight = $input->achievedWeight;
        $created->achievedDurationSeconds = $input->achievedDurationSeconds;
        $created->achievedDistanceMeters = $input->achievedDistanceMeters;
        $created->achievedInclinePercent = $input->achievedInclinePercent;
        $created->achievedInclineMeters = $input->achievedInclineMeters;
        $created->isComplete = ExerciseSetCompletionEvaluator::isComplete($created, $exercise->movement);

        $this->exerciseSetPersister->create($created);
        $exercise->exerciseSets->add($created);

        return $this->mapper->map($created, ExerciseSetDataOutput::class);
    }
}
