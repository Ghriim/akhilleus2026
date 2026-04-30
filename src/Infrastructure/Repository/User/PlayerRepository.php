<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\User;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\User\PlayerProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerDataModel>
 */
final class PlayerRepository extends ServiceEntityRepository implements PlayerProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerDataModel::class);
    }

    public function findOneByUserForLoggedPlayer(UserDataModel $user): ?PlayerDataModel
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->where('p.user = :user')
            ->setParameter('user', $user->id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
