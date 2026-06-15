<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Questing\Quest;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;

interface QuestProviderGateway
{
    /**
     * Every quest active at `$now` (`dateStart ≤ now` and `dateEnd` null-or-future), ordered by
     * `dateStart` ASC. Feeds the player-facing quest listings.
     *
     * @return list<QuestDataModel>
     */
    public function findActiveAtForList(\DateTimeImmutable $now): array;

    /**
     * Active quests of a given periodicity at `$now`, ordered by `dateStart` ASC. The player's
     * matching `QuestProgression` rows are find-or-created on top of this set by the use case.
     *
     * @return list<QuestDataModel>
     */
    public function findActiveByPeriodicityForPlayer(string $periodicity, \DateTimeImmutable $now): array;

    public function findOneByIdForAdminAction(string $id): ?QuestDataModel;

    /**
     * Every quest (active, expired or not-yet-started), ordered by `dateStart` DESC. Feeds the
     * admin quest-management list.
     *
     * @return list<QuestDataModel>
     */
    public function findAllForAdminList(): array;

    /**
     * Active `AUTOMATIC` quests measuring a given metric at `$now`. Feeds
     * `QuestProgressionEvaluator::refreshFor` after a tracking/workout write.
     *
     * @return list<QuestDataModel>
     */
    public function findActiveAutomaticByMetric(string $metric, \DateTimeImmutable $now): array;
}
