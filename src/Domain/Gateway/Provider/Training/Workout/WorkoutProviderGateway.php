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
}
