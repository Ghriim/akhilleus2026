<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
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
}
