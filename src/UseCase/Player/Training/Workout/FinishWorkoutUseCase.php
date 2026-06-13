<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Workout\FinishWorkoutDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\PersonalBestSummaryDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\Training\PersonalBest\PersonalBestPersisterGateway;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\LevelingConfig\LevelingConfigProviderGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
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
        private readonly LevelingConfigProviderGateway $levelingConfigProvider,
        private readonly EarnedExperiencePersisterGateway $earnedExperiencePersister,
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

        $earnedXp = $this->awardWorkoutExperience($workout);

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
            $earnedXp,
        );
    }

    /**
     * Grants XP for a just-completed workout: `round(durationMinutes) × xpPerWorkoutMinute`, stored
     * as an unlocked `EarnedExperience` that the nightly cron (Phase 5/6) later folds into the
     * player's level. Retroactive workouts (completed before the start of today, Europe/Paris) earn
     * nothing — `FinishWorkoutUseCase` always sets `dateEnd = now`, so the guard never trips here,
     * but it keeps the rule explicit for the same-day-edit / retro paths added in Phase 5. Returns
     * the granted amount, or null when no XP was awarded (retroactive or zero-duration workout).
     */
    private function awardWorkoutExperience(WorkoutDataModel $workout): ?int
    {
        $startOfToday = $this->clock->now()
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->setTime(0, 0, 0);
        if (null === $workout->dateEnd || $workout->dateEnd < $startOfToday) {
            return null;
        }

        $durationSeconds = $workout->dateEnd->getTimestamp() - ($workout->dateStart?->getTimestamp() ?? $workout->dateEnd->getTimestamp());
        $durationMinutes = (int) round($durationSeconds / 60);
        $amount = $durationMinutes * $this->levelingConfigProvider->getSingleton()->xpPerWorkoutMinute;
        if (0 >= $amount) {
            return null;
        }

        $this->earnedExperiencePersister->create(new EarnedExperienceDataModel(
            $workout->player,
            'Workout: '.$workout->name,
            $amount,
            $workout->dateEnd,
            EarnedExperienceSourceTypeRegistry::WORKOUT,
            $workout->id,
        ));

        return $amount;
    }
}
