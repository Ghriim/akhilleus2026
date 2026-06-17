<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\DeleteWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Workout\DeleteWorkoutDataOutput;
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
    public const string MODE_HARD = 'hard';
    public const string MODE_SOFT = 'soft';

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
    public function execute(DataInputInterface $input): DeleteWorkoutDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        // The provider is player-scoped and already excludes DELETED workouts (5.2), so an
        // unknown / unowned / already-soft-deleted id surfaces as a 404 — no ownership validator needed.
        $workout = $this->workoutProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->id));
        }

        $deletedId = $workout->id;

        return $this->isDatedToday($workout)
            ? $this->hardDelete($workout, $deletedId)
            : $this->softDelete($workout, $deletedId);
    }

    /**
     * Same-day delete: physically remove the workout. The DB cascades to its exercises + sets
     * (ON DELETE CASCADE) and nulls any personal_best references (ON DELETE SET NULL); the matching
     * XP grant is always still unlocked (the nightly cron only locks entries earned before today),
     * so it is removed too — the unlocked guard is defensive.
     */
    private function hardDelete(WorkoutDataModel $workout, string $deletedId): DeleteWorkoutDataOutput
    {
        $earned = $this->earnedExperienceProvider->findOneBySourceTypeAndId(
            EarnedExperienceSourceTypeRegistry::WORKOUT,
            $workout->id,
        );
        if (null !== $earned && false === $earned->isLocked) {
            $this->earnedExperiencePersister->delete($earned);
        }

        $this->workoutPersister->delete($workout);

        return new DeleteWorkoutDataOutput($deletedId, self::MODE_HARD);
    }

    /**
     * Other-day delete: keep the row but transition it to DELETED so it drops out of every player
     * read (5.2). Any earned XP — possibly already locked by the cron — is preserved untouched.
     */
    private function softDelete(WorkoutDataModel $workout, string $deletedId): DeleteWorkoutDataOutput
    {
        $workout->status = WorkoutStatusRegistry::DELETED;
        $this->workoutPersister->update($workout);

        return new DeleteWorkoutDataOutput($deletedId, self::MODE_SOFT);
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
