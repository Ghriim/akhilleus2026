<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutDataModel>
 */
final class WorkoutRepository extends ServiceEntityRepository implements WorkoutProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutDataModel::class);
    }

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?WorkoutDataModel
    {
        return $this->createQueryBuilder('w')
            ->where('w.id = :id')
            ->andWhere('w.player = :player')
            ->leftJoin('w.exercises', 'e')->addSelect('e')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->orderBy('e.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdForDetails(string $id, PlayerDataModel $player): ?WorkoutDataModel
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.exercises', 'e')->addSelect('e')
            ->leftJoin('e.movement', 'm')->addSelect('m')
            ->leftJoin('m.mainMuscle', 'mm')->addSelect('mm')
            ->leftJoin('e.exerciseSets', 's')->addSelect('s')
            ->where('w.id = :id')
            ->andWhere('w.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->orderBy('e.position', 'ASC')
            ->addOrderBy('s.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCompletedByPlayer(PlayerDataModel $player, int $page, int $perPage): array
    {
        /** @var list<WorkoutDataModel> $result */
        $result = $this->createQueryBuilder('w')
            ->where('w.player = :player')
            ->andWhere('w.status = :status')
            ->setParameter('player', $player)
            ->setParameter('status', WorkoutStatusRegistry::COMPLETED)
            ->orderBy('w.dateEnd', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function countCompletedByPlayer(PlayerDataModel $player): int
    {
        $count = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.player = :player')
            ->andWhere('w.status = :status')
            ->setParameter('player', $player)
            ->setParameter('status', WorkoutStatusRegistry::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    public function findPlannedOrInProgressByPlayer(PlayerDataModel $player): array
    {
        /** @var list<WorkoutDataModel> $result */
        $result = $this->createQueryBuilder('w')
            ->where('w.player = :player')
            ->andWhere('w.status IN (:statuses)')
            ->setParameter('player', $player)
            ->setParameter('statuses', [WorkoutStatusRegistry::PLANNED, WorkoutStatusRegistry::IN_PROGRESS])
            // PLANNED workouts (status string sorts after IN_PROGRESS DESC) come first, then IN_PROGRESS.
            // Within PLANNED, ordered by plannedAt ASC. Within IN_PROGRESS, ordered by dateStart DESC.
            ->orderBy('w.status', 'DESC')
            ->addOrderBy('w.plannedAt', 'ASC')
            ->addOrderBy('w.dateStart', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
