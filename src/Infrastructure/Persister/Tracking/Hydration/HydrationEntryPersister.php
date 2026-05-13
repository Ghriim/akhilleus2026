<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationDailySummaryPersisterGateway;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Domain\Service\Tracking\Hydration\HydrationAggregateEvaluator;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Owns the hydration entry's persistence + the summary-level `amountConsumedMl` aggregate.
 * After every entry mutation (create / update / delete), the parent summary's aggregate is
 * recomputed via `HydrationAggregateEvaluator` and re-persisted through the summary persister
 * gateway. Mirrors the v0 pattern where `WorkoutPersister::update` calls `WorkoutAggregateEvaluator`
 * before `doUpdate` — the persister of the entity that triggers the aggregate change owns the
 * recompute side-effect.
 *
 * Identity-map note (same trap as `AddMovementToWorkoutUseCase` in v0): after `doCreate($entry)`
 * the cached summary's `entries` collection is not auto-synced. We `add($entry)` manually so
 * the in-flight evaluator sees the new entry; symmetric `removeElement` on delete.
 *
 * @extends AbstractBaseMysqlPersister<HydrationEntryDataModel>
 */
final readonly class HydrationEntryPersister extends AbstractBaseMysqlPersister implements HydrationEntryPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
        private HydrationDailySummaryPersisterGateway $summaryPersister,
    ) {
        parent::__construct($entityManager, $clock);
    }

    public function create(HydrationEntryDataModel $model): HydrationEntryDataModel
    {
        $this->doCreate($model);
        $model->summary->entries->add($model);
        $this->refreshSummaryAggregate($model->summary);

        return $model;
    }

    public function update(HydrationEntryDataModel $model): HydrationEntryDataModel
    {
        $this->doUpdate($model);
        $this->refreshSummaryAggregate($model->summary);

        return $model;
    }

    public function delete(HydrationEntryDataModel $model): void
    {
        $summary = $model->summary;
        $summary->entries->removeElement($model);
        $this->doDelete($model);
        $this->refreshSummaryAggregate($summary);
    }

    private function refreshSummaryAggregate(HydrationDailySummaryDataModel $summary): void
    {
        HydrationAggregateEvaluator::recompute($summary);
        $this->summaryPersister->update($summary);
    }
}
