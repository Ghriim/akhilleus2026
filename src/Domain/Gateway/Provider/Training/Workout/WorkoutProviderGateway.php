<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface WorkoutProviderGateway
{
    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?WorkoutDataModel;

    public function findOneByIdForDetails(string $id, PlayerDataModel $player): ?WorkoutDataModel;

    /**
     * @return list<WorkoutDataModel>
     */
    public function findCompletedByPlayer(PlayerDataModel $player, int $page, int $perPage): array;

    public function countCompletedByPlayer(PlayerDataModel $player): int;

    /**
     * @return list<WorkoutDataModel>
     */
    public function findPlannedOrInProgressByPlayer(PlayerDataModel $player): array;

    public function findInProgressByPlayer(PlayerDataModel $player): ?WorkoutDataModel;

    /**
     * Returns every workout owned by the player whose representative date (the most advanced of
     * `dateEnd`, `dateStart`, `plannedAt`) falls inside the half-open interval
     * `[$monthStart, $monthEnd)`.
     *
     * @return list<WorkoutDataModel>
     */
    public function findByPlayerForMonth(
        PlayerDataModel $player,
        \DateTimeImmutable $monthStart,
        \DateTimeImmutable $monthEnd,
    ): array;
}
