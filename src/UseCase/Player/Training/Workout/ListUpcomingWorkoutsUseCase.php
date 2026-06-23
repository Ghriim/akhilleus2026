<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListUpcomingWorkoutsDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListUpcomingWorkoutsUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListUpcomingWorkoutsDataInput $input
     *
     * @return list<WorkoutDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workouts = $this->workoutProvider->findPlannedOrInProgressByPlayer($player);

        return array_map(
            fn (WorkoutDataModel $workout): WorkoutDataOutput => $this->mapper->map($workout, WorkoutDataOutput::class),
            $workouts,
        );
    }
}
