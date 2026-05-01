<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExerciseDataModel>
 */
final class ExerciseRepository extends ServiceEntityRepository implements ExerciseProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseDataModel::class);
    }

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?ExerciseDataModel
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.workout', 'w')->addSelect('w')
            ->innerJoin('e.movement', 'm')->addSelect('m')
            ->where('e.id = :id')
            ->andWhere('w.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByWorkoutIdForPlayerAction(string $workoutId, PlayerDataModel $player): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.workout', 'w')->addSelect('w')
            ->innerJoin('e.movement', 'm')->addSelect('m')
            ->where('w.id = :workoutId')
            ->andWhere('w.player = :player')
            ->orderBy('e.position', 'ASC')
            ->setParameter('workoutId', $workoutId)
            ->setParameter('player', $player)
            ->getQuery()
            ->getResult();
    }
}
