<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\FinishWorkoutDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\PersonalBestSummaryDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\PersonalBest\PersonalBestPersisterGateway;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\PersonalBestEvaluator;
use App\Domain\Validator\Player\Training\Workout\FinishWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class FinishWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly FinishWorkoutValidator $finishWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly WorkoutPersisterGateway $workoutPersister,
        private readonly PersonalBestPersisterGateway $personalBestPersister,
        private readonly PersonalBestEvaluator $personalBestEvaluator,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param FinishWorkoutDataInput $input
     */
    public function execute(DataInputInterface $input): FinishWorkoutDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = $this->workoutProvider->findOneByIdForDetails($input->id, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->id));
        }

        $this->finishWorkoutValidator->validate($player, $input, $workout);

        $workout->status = WorkoutStatusRegistry::COMPLETED;
        $workout->dateEnd = $this->clock->now();
        $this->workoutPersister->update($workout);

        $newPersonalBestsOutput = [];
        foreach ($this->personalBestEvaluator->evaluate($workout) as $upsert) {
            $persisted = true === $upsert->isNew
                ? $this->personalBestPersister->create($upsert->personalBest)
                : $this->personalBestPersister->update($upsert->personalBest);

            $newPersonalBestsOutput[] = new PersonalBestSummaryDataOutput(
                $persisted->movement->id,
                $persisted->movement->slug,
                $persisted->movement->label,
                $persisted->type,
                $persisted->value,
                $persisted->achievedAt->format(\DateTimeInterface::ATOM),
                $persisted->exerciseSet?->id,
            );
        }

        return new FinishWorkoutDataOutput(
            new WorkoutDataOutput(
                $workout->id,
                $workout->name,
                $workout->status,
                $workout->plannedAt?->format(\DateTimeInterface::ATOM),
                $workout->dateStart?->format(\DateTimeInterface::ATOM),
                $workout->dateEnd->format(\DateTimeInterface::ATOM),
                $workout->duration,
                $workout->volume,
                $workout->distance,
                $workout->inclineMeters,
            ),
            $newPersonalBestsOutput,
        );
    }
}
