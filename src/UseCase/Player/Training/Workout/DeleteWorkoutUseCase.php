<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\DeleteWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\EarnedExperience\EarnedExperienceProviderGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Leveling\EarnedExperience\EarnedExperienceSourceTypeRegistry;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class DeleteWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly WorkoutPersisterGateway $workoutPersister,
        private readonly EarnedExperienceProviderGateway $earnedExperienceProvider,
        private readonly EarnedExperiencePersisterGateway $earnedExperiencePersister,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param DeleteWorkoutDataInput $input
     */
    public function execute(DataInputInterface $input): null
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        // The provider is player-scoped and already excludes DELETED workouts (5.2), so an
        // unknown / unowned / already-soft-deleted id surfaces as a 404 — no ownership validator needed.
        $workout = $this->workoutProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->id));
        }

        if ($this->isDatedToday($workout)) {
            $this->hardDelete($workout);
        } else {
            $this->softDelete($workout);
        }

        return null;
    }

    /**
     * Same-day delete: physically remove the workout. The DB cascades to its exercises + sets
     * (ON DELETE CASCADE) and nulls any personal_best references (ON DELETE SET NULL); the matching
     * XP grant is always still unlocked (the nightly cron only locks entries earned before today),
     * so it is removed too — the unlocked guard is defensive.
     */
    private function hardDelete(WorkoutDataModel $workout): void
    {
        $earned = $this->earnedExperienceProvider->findOneBySourceTypeAndId(
            EarnedExperienceSourceTypeRegistry::WORKOUT,
            $workout->id,
        );
        if (null !== $earned && false === $earned->isLocked) {
            $this->earnedExperiencePersister->delete($earned);
        }

        $this->workoutPersister->delete($workout);
    }

    /**
     * Other-day delete: keep the row but transition it to DELETED so it drops out of every player
     * read (5.2). Any earned XP — possibly already locked by the cron — is preserved untouched.
     */
    private function softDelete(WorkoutDataModel $workout): void
    {
        $workout->status = WorkoutStatusRegistry::DELETED;
        $this->workoutPersister->update($workout);
    }

    /**
     * A workout counts as "today" when its representative date — the codebase precedence
     * dateEnd → dateStart → plannedAt — falls inside today's Europe/Paris day. A date-less workout
     * (all three null) is treated as not-today and soft-deleted.
     */
    private function isDatedToday(WorkoutDataModel $workout): bool
    {
        $reference = $workout->dateEnd ?? $workout->dateStart ?? $workout->plannedAt;
        if (null === $reference) {
            return false;
        }

        $startOfToday = $this->clock->now()
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->setTime(0, 0, 0);

        return $reference >= $startOfToday && $reference < $startOfToday->modify('+1 day');
    }
}
