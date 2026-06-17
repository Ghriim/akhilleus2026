<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Leveling\EarnedExperience;

use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Leveling\EarnedExperience\EarnedExperiencePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<EarnedExperienceDataModel>
 */
final readonly class EarnedExperiencePersister extends AbstractBaseMysqlPersister implements EarnedExperiencePersisterGateway
{
    public const string EARNED_EXPERIENCE_LOCKED = 'EARNED_EXPERIENCE_LOCKED';

    public function create(EarnedExperienceDataModel $model): EarnedExperienceDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(EarnedExperienceDataModel $model): EarnedExperienceDataModel
    {
        $this->assertNotLocked($model);
        $this->doUpdate($model);

        return $model;
    }

    public function delete(EarnedExperienceDataModel $model): void
    {
        $this->assertNotLocked($model);
        $this->doDelete($model);
    }

    /**
     * Reject any mutation of an entry that is *already* locked in the database. The check reads the
     * persisted (prior) state via the UnitOfWork, not `$model->isLocked`, so the nightly leveling
     * cron can still transition an unlocked entry to `isLocked = true` (original state = false → allowed).
     */
    private function assertNotLocked(EarnedExperienceDataModel $model): void
    {
        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($model);
        $wasLocked = (bool) ($original['isLocked'] ?? $model->isLocked);

        if (true === $wasLocked) {
            throw new ValidationException('Cannot mutate a locked earned experience entry.', ['isLocked' => ['This earned experience is locked and can no longer be modified.']], self::EARNED_EXPERIENCE_LOCKED);
        }
    }
}
