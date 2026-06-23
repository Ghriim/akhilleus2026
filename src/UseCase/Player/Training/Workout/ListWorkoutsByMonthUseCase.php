<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutsByMonthDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\ListWorkoutsByMonthValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ListWorkoutsByMonthUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly ListWorkoutsByMonthValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param ListWorkoutsByMonthDataInput $input
     *
     * @return list<WorkoutDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $monthStart = new \DateTimeImmutable(
            sprintf('%04d-%02d-01T00:00:00', $input->year, $input->month),
            new \DateTimeZone('UTC'),
        );
        $monthEnd = $monthStart->modify('+1 month');

        $workouts = $this->workoutProvider->findByPlayerForMonth($player, $monthStart, $monthEnd);

        usort(
            $workouts,
            static fn (WorkoutDataModel $a, WorkoutDataModel $b) => self::referenceDate($a) <=> self::referenceDate($b),
        );

        return array_map(
            fn (WorkoutDataModel $workout): WorkoutDataOutput => $this->mapper->map($workout, WorkoutDataOutput::class),
            $workouts,
        );
    }

    private static function referenceDate(WorkoutDataModel $workout): \DateTimeImmutable
    {
        return $workout->dateEnd ?? $workout->dateStart ?? $workout->plannedAt
            ?? throw new \LogicException(sprintf('Workout %s has no date.', $workout->id));
    }
}
