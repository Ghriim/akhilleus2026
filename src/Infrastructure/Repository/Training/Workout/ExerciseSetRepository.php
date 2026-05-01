<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseSetProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExerciseSetDataModel>
 */
final class ExerciseSetRepository extends ServiceEntityRepository implements ExerciseSetProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseSetDataModel::class);
    }

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?ExerciseSetDataModel
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.exercise', 'e')->addSelect('e')
            ->innerJoin('e.workout', 'w')->addSelect('w')
            ->innerJoin('e.movement', 'm')->addSelect('m')
            ->where('s.id = :id')
            ->andWhere('w.player = :player')
            ->setParameter('id', $id)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByExerciseIdForPlayerAction(string $exerciseId, PlayerDataModel $player): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.exercise', 'e')->addSelect('e')
            ->innerJoin('e.workout', 'w')
            ->where('e.id = :exerciseId')
            ->andWhere('w.player = :player')
            ->orderBy('s.position', 'ASC')
            ->setParameter('exerciseId', $exerciseId)
            ->setParameter('player', $player)
            ->getQuery()
            ->getResult();
    }
}
