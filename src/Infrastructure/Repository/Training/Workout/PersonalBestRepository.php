<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Training\Workout;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Provider\Training\PersonalBest\PersonalBestProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonalBestDataModel>
 */
final class PersonalBestRepository extends ServiceEntityRepository implements PersonalBestProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalBestDataModel::class);
    }

    public function findOneForPlayerMovementType(PlayerDataModel $player, MovementDataModel $movement, string $type): ?PersonalBestDataModel
    {
        return $this->createQueryBuilder('p')
            ->where('p.player = :player')
            ->andWhere('p.movement = :movement')
            ->andWhere('p.type = :type')
            ->setParameter('player', $player)
            ->setParameter('movement', $movement)
            ->setParameter('type', $type)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
