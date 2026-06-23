<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\PlanWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\PlanWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class PlanWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly PlanWorkoutValidator $planWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutPersisterGateway $workoutPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param PlanWorkoutDataInput $input
     */
    public function execute(DataInputInterface $input): WorkoutDataOutput
    {
        $this->planWorkoutValidator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::PLANNED);
        $workout->plannedAt = $input->plannedAt;

        $this->workoutPersister->create($workout);

        return $this->mapper->map($workout, WorkoutDataOutput::class);
    }
}
